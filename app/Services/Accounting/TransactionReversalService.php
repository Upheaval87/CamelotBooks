<?php

namespace App\Services\Accounting;

use App\Models\AccountAuditLog;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ReversalApprovalHistory;
use App\Models\ReversalAuthorizationRequest;
use App\Models\ReversalAuthorizationRule;
use App\Models\TransactionReversal;
use App\Models\TransactionReversalRequest;
use App\Models\User;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;

class TransactionReversalService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private NumberingSequenceService $numberingService,
    ) {}

    public function searchTransactions(int $companyId, array $filters = [])
    {
        $query = JournalEntry::forCompany($companyId)
            ->with(['lines', 'createdBy', 'linkedEntry'])
            ->whereIn('status', ['posted', 'reversed']);

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['type'])) {
            $query->where('source_module', $filters['type']);
        }
        if (!empty($filters['exclude_reversal'])) {
            // A reversal entry can never be reversed again, so it must never be a
            // capture candidate. Keep NULL source_module entries (manual journals).
            $query->where(function ($q) {
                $q->whereNull('source_module')->orWhere('source_module', '!=', 'reversal');
            });
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['account_id'])) {
            $query->whereHas('lines', fn ($q) => $q->where('account_id', $filters['account_id']));
        }
        if (!empty($filters['min_amount'])) {
            $query->whereHas('lines', function ($q) use ($filters) {
                $q->selectRaw('SUM(debit) as total_debit')
                    ->groupBy('journal_entry_id')
                    ->havingRaw('SUM(debit) >= ?', [$filters['min_amount']]);
            });
        }
        if (!empty($filters['max_amount'])) {
            $query->whereHas('lines', function ($q) use ($filters) {
                $q->selectRaw('SUM(debit) as total_debit')
                    ->groupBy('journal_entry_id')
                    ->havingRaw('SUM(debit) <= ?', [$filters['max_amount']]);
            });
        }
        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('journal_number', 'like', "%{$filters['q']}%")
                    ->orWhere('memo', 'like', "%{$filters['q']}%");
            });
        }

        return $query->orderBy('date', 'desc')->paginate(15);
    }

    public function requestReversal(int $companyId, int $journalEntryId, int $userId, array $data): TransactionReversalRequest
    {
        return DB::transaction(function () use ($companyId, $journalEntryId, $userId, $data) {
            $je = JournalEntry::where('id', $journalEntryId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $this->guardReversal($je, $companyId);

            $referenceNumber = $this->numberingService->getNextNumber(
                $companyId,
                'transaction_reversal'
            );

            $amount = $je->total_debit;

            $request = TransactionReversalRequest::create([
                'company_id' => $companyId,
                'reference_number' => $referenceNumber,
                'journal_entry_id' => $je->id,
                'original_transaction_type' => $je->source_module ?? 'journal_entry',
                'original_transaction_id' => $je->id,
                'requested_by' => $userId,
                'request_date' => now()->toDateString(),
                'reversal_date' => $data['reversal_date'] ?? $je->date,
                'reversal_method' => $data['reversal_method'] ?? 'full',
                'partial_amount' => $data['partial_amount'] ?? null,
                'reason' => $data['reason'],
                'status' => TransactionReversalRequest::STATUS_PENDING,
            ]);

            $this->logHistory($request, 'requested', $userId, $data['reason']);

            $rules = $this->matchingRules($companyId, $amount, $je->source_module);
            $this->createAuthorizationChain($request, $rules, $companyId);

            return $request;
        });
    }

    public function approve(int $requestId, int $userId, ?string $comments = null): TransactionReversalRequest
    {
        return DB::transaction(function () use ($requestId, $userId, $comments) {
            $request = TransactionReversalRequest::findOrFail($requestId);

            $this->authorizeUser($request, $userId);

            $this->logHistory($request, 'approved', $userId, $comments);

            // Multi-level chains only execute once every authorizer has signed off.
            $remaining = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
                ->where('status', 'pending')
                ->count();

            if ($remaining > 0) {
                return $request->fresh();
            }

            $request->update([
                'status' => TransactionReversalRequest::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_date' => now(),
            ]);

            $this->executeReversal($request, $userId);

            return $request->fresh();
        });
    }

    public function reject(int $requestId, int $userId, string $reason): TransactionReversalRequest
    {
        return DB::transaction(function () use ($requestId, $userId, $reason) {
            $request = TransactionReversalRequest::findOrFail($requestId);

            $this->authorizeUser($request, $userId);

            // Clear the rest of the chain so the rejected request leaves every queue.
            ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'approved_by' => $userId,
                    'approved_date' => now(),
                ]);

            $request->update([
                'status' => TransactionReversalRequest::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'approved_by' => $userId,
                'approved_date' => now(),
            ]);

            $this->logHistory($request, 'rejected', $userId, $reason);

            return $request->fresh();
        });
    }

    public function requestClarification(int $requestId, int $userId, string $message): TransactionReversalRequest
    {
        return DB::transaction(function () use ($requestId, $userId, $message) {
            $request = TransactionReversalRequest::findOrFail($requestId);

            $this->authorizeUser($request, $userId);

            $request->update(['status' => TransactionReversalRequest::STATUS_CLARIFICATION]);

            $this->logHistory($request, 'clarification_requested', $userId, $message);

            return $request->fresh();
        });
    }

    public function getDashboardStats(int $companyId): array
    {
        $total = TransactionReversalRequest::forCompany($companyId)->count();
        $pending = TransactionReversalRequest::forCompany($companyId)->pending()->count();
        $approved = TransactionReversalRequest::forCompany($companyId)
            ->where('status', TransactionReversalRequest::STATUS_APPROVED)->count();
        $reversed = TransactionReversalRequest::forCompany($companyId)
            ->where('status', TransactionReversalRequest::STATUS_REVERSED)->count();
        $rejected = TransactionReversalRequest::forCompany($companyId)
            ->where('status', TransactionReversalRequest::STATUS_REJECTED)->count();

        return compact('total', 'pending', 'approved', 'reversed', 'rejected');
    }

    public function getAuthDashboardStats(int $companyId, int $userId): array
    {
        $myQueue = ReversalAuthorizationRequest::forCompany($companyId)
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->count();

        $totalPending = ReversalAuthorizationRequest::forCompany($companyId)
            ->where('status', 'pending')
            ->count();

        $totalApproved = ReversalAuthorizationRequest::forCompany($companyId)
            ->where('status', 'approved')
            ->count();

        $totalRejected = ReversalAuthorizationRequest::forCompany($companyId)
            ->where('status', 'rejected')
            ->count();

        return compact('myQueue', 'totalPending', 'totalApproved', 'totalRejected');
    }

    /**
     * Backfill authorization chains for legacy pending requests that have no
     * authorization rows (captured before the default chain always produced a
     * row). Idempotent: requests that already have rows are left untouched.
     */
    public function ensureAuthorizationChains(int $companyId): void
    {
        $orphans = TransactionReversalRequest::forCompany($companyId)
            ->where('status', TransactionReversalRequest::STATUS_PENDING)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reversal_authorization_requests')
                    ->whereColumn(
                        'reversal_authorization_requests.reversal_request_id',
                        'transaction_reversal_requests.id'
                    );
            })
            ->with('journalEntry')
            ->get();

        foreach ($orphans as $request) {
            $rules = $this->matchingRules(
                $companyId,
                (float) ($request->journalEntry?->total_debit ?? 0),
                $request->original_transaction_type,
            );

            $this->createAuthorizationChain($request, $rules, $companyId);
        }
    }

    public function getRulesStats(int $companyId): array
    {
        $query = ReversalAuthorizationRule::forCompany($companyId);

        $activeRules = (clone $query)->active()->count();
        $inactive = (clone $query)->where('active', false)->count();
        $multiStepRequired = (clone $query)->where('required_approvals', '>', 1)->count();
        $totalReversals = $activeRules + $inactive;

        return compact('activeRules', 'inactive', 'multiStepRequired', 'totalReversals');
    }

    public function storeRule(int $companyId, array $data): ReversalAuthorizationRule
    {
        return ReversalAuthorizationRule::create(array_merge($data, [
            'company_id' => $companyId,
        ]));
    }

    public function updateRule(int $ruleId, int $companyId, array $data): ReversalAuthorizationRule
    {
        $rule = ReversalAuthorizationRule::where('id', $ruleId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $rule->update($data);

        return $rule->fresh();
    }

    public function toggleRule(int $ruleId, int $companyId): ReversalAuthorizationRule
    {
        $rule = ReversalAuthorizationRule::where('id', $ruleId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $rule->update(['active' => !$rule->active]);

        return $rule->fresh();
    }

    public function deleteRule(int $ruleId, int $companyId): bool
    {
        return (bool) ReversalAuthorizationRule::where('id', $ruleId)
            ->where('company_id', $companyId)
            ->delete();
    }

    private function guardReversal(JournalEntry $je, int $companyId): void
    {
        if ($je->isReversed()) {
            abort(422, 'This journal entry has already been reversed.');
        }
        if (!$je->isPosted()) {
            abort(422, 'Only posted journal entries can be reversed.');
        }

        $hasOpenRequest = TransactionReversalRequest::where('company_id', $companyId)
            ->where('journal_entry_id', $je->id)
            ->whereIn('status', [
                TransactionReversalRequest::STATUS_PENDING,
                TransactionReversalRequest::STATUS_APPROVED,
            ])
            ->exists();

        if ($hasOpenRequest) {
            abort(422, 'A reversal for this transaction is already awaiting authorization.');
        }

        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $je->date)
            ->where('end_date', '>=', $je->date)
            ->first();

        if ($period && ($period->isClosed() || $period->isLocked())) {
            abort(422, 'The accounting period for this entry is closed or locked.');
        }

        $year = FiscalYear::where('company_id', $companyId)
            ->where('start_date', '<=', $je->date)
            ->where('end_date', '>=', $je->date)
            ->first();

        if ($year && $year->status === 'closed') {
            abort(422, 'The fiscal year for this entry is locked.');
        }

        $hasDependent = JournalEntry::where('linked_entry_id', $je->id)
            ->where('status', '!=', 'reversed')
            ->exists();

        if ($hasDependent) {
            abort(422, 'This journal entry has dependent entries that must be handled first.');
        }
    }

    private function matchingRules(int $companyId, float $amount, ?string $type): \Illuminate\Support\Collection
    {
        return ReversalAuthorizationRule::forCompany($companyId)
            ->active()
            ->forAmount($amount)
            ->forType($type)
            ->get();
    }

    private function createAuthorizationChain(
        TransactionReversalRequest $request,
        \Illuminate\Support\Collection $rules,
        int $companyId,
    ): void {
        $level = 1;
        foreach ($rules as $rule) {
            for ($i = 0; $i < $rule->required_approvals; $i++) {
                ReversalAuthorizationRequest::create([
                    'company_id' => $companyId,
                    'reversal_request_id' => $request->id,
                    'approval_level' => $level,
                    'assigned_to' => $this->resolveApprover($rule, (int) $request->requested_by, $companyId),
                    'status' => 'pending',
                ]);
                $level++;
            }
        }

        if ($level === 1) {
            // No rule matched: guarantee the request is always authorizable by
            // creating one row, preferring an accountant (never the requester)
            // and falling back to any other company member when none exists.
            ReversalAuthorizationRequest::create([
                'company_id' => $companyId,
                'reversal_request_id' => $request->id,
                'approval_level' => 1,
                'assigned_to' => $this->resolveDefaultApprover((int) $request->requested_by, $companyId),
                'status' => 'pending',
            ]);
        }
    }

    private function resolveDefaultApprover(int $requesterId, int $companyId): int
    {
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'accountant');
        })
            ->where('id', '!=', $requesterId)
            ->where(fn ($q) => $this->restrictToCompanyMembers($q, $companyId))
            ->orderBy('id')
            ->value('id')
            ?? $this->memberApproverId($requesterId, $companyId)
            ?? $requesterId;
    }

    private function resolveApprover(ReversalAuthorizationRule $rule, int $requesterId, int $companyId): int
    {
        return User::whereHas('roles', function ($q) use ($rule) {
            $q->where('name', $rule->approver_role);
        })
            ->where('id', '!=', $requesterId)
            ->where(fn ($q) => $this->restrictToCompanyMembers($q, $companyId))
            ->orderBy('id')
            ->value('id')
            ?? $this->memberApproverId($requesterId, $companyId)
            ?? $requesterId;
    }

    /**
     * First (lowest-id) user with access to the company, never the requester.
     * Falls back to any other user, then to the requester as a last resort.
     */
    private function memberApproverId(int $requesterId, int $companyId): ?int
    {
        return User::where('id', '!=', $requesterId)
            ->where(fn ($q) => $this->restrictToCompanyMembers($q, $companyId))
            ->orderBy('id')
            ->value('id')
            ?? User::where('id', '!=', $requesterId)->orderBy('id')->value('id');
    }

    private function restrictToCompanyMembers($query, int $companyId): void
    {
        $query->where(function ($q) use ($companyId) {
            $q->whereHas('activeCompanyAssignments', fn ($a) => $a->where('company_id', $companyId))
                ->orWhereHas('companies', fn ($c) => $c->whereKey($companyId));
        });
    }

    private function executeReversal(TransactionReversalRequest $request, int $userId): void
    {
        $reversalEntry = $this->postingEngine->reverse(
            $request->journal_entry_id,
            $userId,
            $request->reversal_date?->format('Y-m-d'),
        );

        $reversalNumber = $this->numberingService->getNextNumber(
            $request->company_id,
            'transaction_reversal'
        );

        TransactionReversal::create([
            'company_id' => $request->company_id,
            'reversal_request_id' => $request->id,
            'original_journal_entry_id' => $request->journal_entry_id,
            'reversal_journal_entry_id' => $reversalEntry->id,
            'reversal_number' => $reversalNumber,
            'reversal_date' => $request->reversal_date,
            'amount' => $request->journalEntry->total_debit,
            'created_by' => $userId,
        ]);

        $request->update(['status' => TransactionReversalRequest::STATUS_REVERSED]);

        $this->logHistory($request, 'posted_to_gl', $userId, 'Reversal entry ' . $reversalNumber . ' posted to the general ledger.');

        AccountAuditLog::create([
            'company_id' => $request->company_id,
            'journalable_type' => JournalEntry::class,
            'journalable_id' => $reversalEntry->id,
            'action' => 'reversal_completed',
            'old_values' => ['original_je_id' => $request->journal_entry_id],
            'new_values' => ['reversal_je_id' => $reversalEntry->id, 'request_id' => $request->id],
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    private function authorizeUser(TransactionReversalRequest $request, int $userId): void
    {
        $auth = ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->orderBy('approval_level')
            ->orderBy('id')
            ->first();

        // Fall back to the next pending row so a permitted, non-requester
        // approver can act even when the chain did not assign them directly
        // (e.g. no accountant exists and the row went to another user).
        $auth ??= ReversalAuthorizationRequest::where('reversal_request_id', $request->id)
            ->where('status', 'pending')
            ->orderBy('approval_level')
            ->orderBy('id')
            ->first();

        abort_unless($auth, 403, 'You are not authorized to act on this request.');

        $auth->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_date' => now(),
        ]);
    }

    private function logHistory(TransactionReversalRequest $request, string $action, int $userId, ?string $remarks): void
    {
        ReversalApprovalHistory::create([
            'company_id' => $request->company_id,
            'reversal_request_id' => $request->id,
            'action' => $action,
            'performed_by' => $userId,
            'remarks' => $remarks,
            'date_time' => now(),
        ]);
    }
}
