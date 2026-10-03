<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\ReversalAuthorizationRequest;
use App\Policies\TransactionControlPolicy;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Accounting\JournalReversalService;
use App\Services\Accounting\TransactionReversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Transaction Controls workspace.
 *
 * A unified surface over the existing reversal / posting infrastructure:
 *  - Pane 1 "Capture Reversal"  -> posted journals + reversal capture
 *  - Pane 2 "Unposted Transactions" -> draft / pending-approval journals
 *  - Pane 3 "Authorization"     -> reversal authorization queue
 *
 * This controller is a view layer: it delegates to the existing services and
 * never duplicates ledger logic.
 */
class TransactionControlsController extends Controller
{
    public function __construct(
        private TransactionReversalService $reversalService,
        private JournalReversalService $journalReversalService,
        private JournalPostingEngine $postingEngine,
        private TransactionControlPolicy $policy,
    ) {}

    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');
        $user = $request->user();

        abort_unless($this->policy->viewReversals($user), 403);

        $tab = $this->resolveTab($request);

        $from = $request->query('from');
        $to = $request->query('to');
        $dateError = null;
        $loaded = false;

        if ($from && $to) {
            if ($from > $to) {
                $dateError = 'The From date must be on or before the To date.';
            } else {
                $loaded = true;
            }
        } elseif ($from || $to) {
            $dateError = 'Select both a From and a To date to load transactions.';
        }

        $filters = $request->only(['type', 'branch_id', 'account_id', 'min_amount', 'max_amount', 'q']);

        $transactions = null;
        if ($loaded) {
            $transactions = $this->reversalService
                ->searchTransactions($companyId, array_merge($filters, [
                    'date_from' => $from,
                    'date_to' => $to,
                ]))
                ->appends($request->query());

            $transactions->getCollection()->loadMissing('lines.account');
        }

        $reversalCount = $loaded
            ? $transactions->total()
            : JournalEntry::forCompany($companyId)->whereIn('status', ['posted', 'reversed'])->count();

        $unposted = JournalEntry::forCompany($companyId)
            ->whereIn('status', [JournalEntry::STATUS_DRAFT, JournalEntry::STATUS_PENDING_APPROVAL])
            ->with(['lines.account', 'createdBy'])
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'unposted_page')
            ->appends($request->query());

        $authQueue = ReversalAuthorizationRequest::forCompany($companyId)
            ->where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->with(['request.journalEntry.lines.account', 'request.requester'])
            ->orderBy('approval_level')
            ->orderBy('id')
            ->get();

        $authDecided = ReversalAuthorizationRequest::forCompany($companyId)
            ->whereIn('status', ['approved', 'rejected'])
            ->with(['request.journalEntry', 'request.requester', 'approver'])
            ->orderByDesc('approved_date')
            ->limit(15)
            ->get();

        $counts = [
            'reversal' => $reversalCount,
            'unposted' => $unposted->total(),
            'authorization' => $authQueue->count(),
        ];

        $typeOptions = JournalEntry::forCompany($companyId)
            ->whereIn('status', ['posted', 'reversed'])
            ->whereNotNull('source_module')
            ->distinct()
            ->orderBy('source_module')
            ->pluck('source_module')
            ->mapWithKeys(fn ($module) => [$module => $this->typeLabel($module)])
            ->all();

        $viewEntryPayload = null;
        if ($request->filled('view')) {
            $entry = JournalEntry::forCompany($companyId)
                ->with(['lines.account', 'createdBy'])
                ->find($request->integer('view'));
            $viewEntryPayload = $entry ? $this->entryPayload($entry) : null;
        }

        $authPayload = null;
        if ($request->filled('auth')) {
            $auth = ReversalAuthorizationRequest::forCompany($companyId)
                ->with(['request.journalEntry.lines.account', 'request.requester'])
                ->find($request->integer('auth'));
            $authPayload = $auth ? $this->authPayload($auth) : null;
        }

        $workspaceConfig = [
            'tab' => $tab,
            'transactions' => $transactions
                ? $transactions->getCollection()->map(fn ($e) => $this->entryPayload($e))->values()->all()
                : [],
            'unposted' => $unposted->getCollection()->map(fn ($e) => $this->entryPayload($e))->values()->all(),
            'authRows' => $authQueue->concat($authDecided)
                ->map(fn ($a) => $this->authPayload($a))->values()->all(),
            'viewEntry' => $viewEntryPayload,
            'authPayload' => $authPayload,
            'currencySymbol' => $this->currencySymbol($companyId),
            'threshold' => JournalReversalService::identityVerifyThreshold(),
            'urls' => [
                'reverse' => route('accounting.transaction-controls.reverse', ['id' => '__ID__']),
                'reopen' => route('accounting.transaction-controls.reopen', ['id' => '__ID__']),
                'destroy' => route('accounting.transaction-controls.destroy', ['id' => '__ID__']),
                'approve' => route('accounting.transaction-controls.approve', ['id' => '__ID__']),
                'reject' => route('accounting.transaction-controls.reject', ['id' => '__ID__']),
            ],
        ];

        return view('accounting.transaction-controls.index', compact(
            'tab', 'counts', 'from', 'to', 'loaded', 'dateError', 'filters',
            'transactions', 'unposted', 'authQueue', 'authDecided',
            'typeOptions', 'workspaceConfig'
        ));
    }

    public function reverse(Request $request, int $id)
    {
        $this->requirePermission($request, 'transaction-reversals.request');

        $companyId = (int) session('current_company_id');
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'mode' => 'required|in:immediate,authorization,draft',
            'reversal_date' => 'required|date',
            'reason' => 'required|string|min:10|max:1000',
            'reference' => 'nullable|string|max:60',
        ]);

        $entry = JournalEntry::forCompany($companyId)->findOrFail($id);

        abort_unless($this->policy->captureReversal($request->user()), 403);

        try {
            switch ($validated['mode']) {
                case 'immediate':
                    $this->journalReversalService->createAndPost($entry, $userId, [
                        'reversal_date' => $validated['reversal_date'],
                        'reference' => $validated['reference'] ?? null,
                        'memo' => $validated['reason'],
                    ]);
                    $message = 'Reversal posted — ' . $entry->journal_number . ' has been reversed.';
                    break;

                case 'draft':
                    $this->journalReversalService->createDraft($entry, $userId, [
                        'reversal_date' => $validated['reversal_date'],
                        'reference' => $validated['reference'] ?? null,
                        'memo' => $validated['reason'],
                    ]);
                    $message = 'Reversal saved as a draft. It appears under Unposted Transactions.';
                    break;

                default:
                    $this->reversalService->requestReversal($companyId, $entry->id, $userId, [
                        'reversal_date' => $validated['reversal_date'],
                        'reason' => $validated['reason'],
                        'reversal_method' => 'full',
                    ]);
                    $message = 'Reversal submitted for authorization.';
                    break;
            }

            return $this->workspaceRedirect($request, 'reversal', 'success', $message);
        } catch (\InvalidArgumentException|HttpExceptionInterface $e) {
            return $this->workspaceRedirect($request, 'reversal', 'error', $e->getMessage());
        }
    }

    public function reopen(Request $request, int $id)
    {
        $this->requirePermission($request, 'journal-entries.edit');

        $companyId = (int) session('current_company_id');
        $userId = (int) Auth::id();

        $entry = JournalEntry::forCompany($companyId)->findOrFail($id);

        abort_unless($this->policy->manageUnposted($request->user(), $entry), 403);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->postingEngine->reopen($entry->id, $userId, $validated['reason'] ?? null);
        } catch (\InvalidArgumentException|HttpExceptionInterface $e) {
            return $this->workspaceRedirect($request, 'unposted', 'error', $e->getMessage());
        }

        return $this->workspaceRedirect($request, 'unposted', 'success', 'Transaction reopened to draft.');
    }

    public function destroy(Request $request, int $id)
    {
        $companyId = (int) session('current_company_id');
        $userId = (int) Auth::id();

        $entry = JournalEntry::forCompany($companyId)->findOrFail($id);

        abort_unless($this->policy->deleteUnposted($request->user(), $entry), 403);

        try {
            $this->postingEngine->deleteDraft($entry, $userId);
        } catch (\InvalidArgumentException|HttpExceptionInterface $e) {
            return $this->workspaceRedirect($request, 'unposted', 'error', $e->getMessage());
        }

        return $this->workspaceRedirect($request, 'unposted', 'success', 'Draft transaction deleted.');
    }

    public function approve(Request $request, int $id)
    {
        $this->requirePermission($request, 'transaction-reversals.approve');

        $companyId = (int) session('current_company_id');
        $userId = (int) Auth::id();

        $auth = ReversalAuthorizationRequest::forCompany($companyId)->findOrFail($id);
        $requesterId = optional($auth->request)->requested_by;

        abort_unless($this->policy->dualControl($request->user(), $requesterId), 403);

        $validated = $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reversalService->approve($auth->reversal_request_id, $userId, $validated['comments'] ?? null);
        } catch (\InvalidArgumentException|HttpExceptionInterface $e) {
            return $this->workspaceRedirect($request, 'authorization', 'error', $e->getMessage());
        }

        return $this->workspaceRedirect($request, 'authorization', 'success', 'Reversal authorized and posted to the ledger.');
    }

    public function reject(Request $request, int $id)
    {
        $this->requirePermission($request, 'transaction-reversals.reject');

        $companyId = (int) session('current_company_id');
        $userId = (int) Auth::id();

        $auth = ReversalAuthorizationRequest::forCompany($companyId)->findOrFail($id);
        $requesterId = optional($auth->request)->requested_by;

        abort_unless($this->policy->dualControl($request->user(), $requesterId), 403);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);

        try {
            $this->reversalService->reject($auth->reversal_request_id, $userId, $validated['reason']);
        } catch (\InvalidArgumentException|HttpExceptionInterface $e) {
            return $this->workspaceRedirect($request, 'authorization', 'error', $e->getMessage());
        }

        return $this->workspaceRedirect($request, 'authorization', 'success', 'Reversal request rejected.');
    }

    private function resolveTab(Request $request): string
    {
        $tab = (string) $request->query('tab', 'reversal');

        return in_array($tab, ['reversal', 'unposted', 'authorization'], true) ? $tab : 'reversal';
    }

    private function workspaceRedirect(Request $request, string $tab, string $flashType, string $message)
    {
        $params = array_filter([
            'tab' => $tab,
            'from' => $request->input('from', $request->query('from')),
            'to' => $request->input('to', $request->query('to')),
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('accounting.transaction-controls.index', $params)
            ->with($flashType, $message);
    }

    private function entryPayload(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'ref' => $entry->journal_number,
            'type' => $entry->source_module,
            'typeLabel' => $this->typeLabel($entry->source_module),
            'date' => optional($entry->date)->format('Y-m-d'),
            'saved' => optional($entry->updated_at)->format('Y-m-d'),
            'desc' => $entry->memo,
            'sub' => $entry->reference,
            'amount' => (float) $entry->total_debit,
            'status' => $entry->status,
            'state' => $entry->status === JournalEntry::STATUS_DRAFT ? 'Draft' : 'Finalized',
            'postedBy' => optional($entry->createdBy)->name,
            'creator' => optional($entry->createdBy)->name,
            'createdBy' => $entry->created_by,
            'reversible' => $entry->isPosted(),
            'lines' => $entry->lines->map(fn ($line) => [
                'code' => optional($line->account)->code,
                'name' => optional($line->account)->name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'memo' => $line->memo,
            ])->values()->all(),
        ];
    }

    private function authPayload(ReversalAuthorizationRequest $auth): array
    {
        $request = $auth->request;
        $entry = $request?->journalEntry;
        $requesterId = $request?->requested_by;

        return [
            'id' => $auth->id,
            'requestId' => $auth->reversal_request_id,
            'ref' => $request?->reference_number,
            'type' => $request?->original_transaction_type,
            'typeLabel' => $this->typeLabel($request?->original_transaction_type),
            'amount' => (float) ($entry?->total_debit ?? 0),
            'reason' => $request?->reason,
            'requester' => optional($request?->requester)->name,
            'requesterId' => $requesterId,
            'level' => $auth->approval_level,
            'submitted' => optional($request?->request_date)->format('Y-m-d')
                ?? optional($request?->created_at)->format('Y-m-d'),
            'status' => $auth->status,
            'entry' => $entry ? $this->entryPayload($entry) : null,
        ];
    }

    private function typeLabel(?string $module): string
    {
        return match ($module) {
            'journal_entry', null, '' => 'Journal Entry',
            'invoice' => 'Invoice',
            'bill' => 'Bill',
            'customer_receipt' => 'Customer Receipt',
            'vendor_payment' => 'Vendor Payment',
            'sales_receipt' => 'Sales Receipt',
            'pos_sale' => 'POS Sale',
            'payroll_run' => 'Payroll Run',
            'deposit' => 'Bank Deposit',
            'transfer' => 'Bank Transfer',
            'reversal' => 'Reversal',
            default => ucwords(str_replace('_', ' ', $module)),
        };
    }

    private function currencySymbol(int $companyId): string
    {
        return (string) \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
    }
}
