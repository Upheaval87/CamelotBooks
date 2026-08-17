<?php

namespace App\Services\Accounting;

use App\Models\Budget;
use App\Models\BudgetAdjustment;
use App\Models\BudgetAuditLog;
use App\Models\BudgetLine;
use App\Models\BudgetTemplate;
use App\Models\NumberingSequence;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Create a new budget with lines.
     */
    public function create(array $data, int $userId): Budget
    {
        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];
            $code = $this->nextCode($companyId);

            $budget = Budget::create([
                'company_id'     => $companyId,
                'name'           => $data['name'],
                'code'           => $code,
                'type'           => $data['type'] ?? 'operating',
                'fiscal_year_id' => $data['fiscal_year_id'],
                'period'         => $data['period'] ?? 'annual',
                'department'     => $data['department'] ?? null,
                'branch_id'      => $data['branch_id'] ?? null,
                'project'        => $data['project'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'status'         => 'draft',
                'currency'       => $data['currency'] ?? 'MWK',
                'total_income'   => 0,
                'total_expenses' => 0,
                'prepared_by'    => $userId,
            ]);

            if (!empty($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    $this->addLine($budget, $lineData);
                }
                $this->recalculateTotals($budget);
            }

            BudgetAuditLog::create([
                'company_id'  => $companyId,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'created',
                'after'       => $budget->toArray(),
                'description' => "Budget {$budget->code} created",
                'created_at'  => now(),
            ]);

            return $budget;
        });
    }

    /**
     * Update an existing draft/rejected budget.
     */
    public function update(Budget $budget, array $data, int $userId): Budget
    {
        if (!$budget->isEditable()) {
            throw new \RuntimeException('This budget cannot be edited in its current status.');
        }

        return DB::transaction(function () use ($budget, $data, $userId) {
            $before = $budget->toArray();

            $budget->update(collect($data)->only([
                'name', 'type', 'fiscal_year_id', 'period',
                'department', 'branch_id', 'project', 'cost_center_id',
                'currency',
            ])->toArray());

            if (!empty($data['lines']) && is_array($data['lines'])) {
                // Sync lines: update existing, create new, delete removed
                $existingLineIds = collect($data['lines'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Remove lines not in the update payload
                $budget->lines()
                    ->whereNotIn('id', $existingLineIds)
                    ->delete();

                foreach ($data['lines'] as $lineData) {
                    if (!empty($lineData['id'])) {
                        $line = BudgetLine::find($lineData['id']);
                        if ($line && $line->budget_id === $budget->id) {
                            $line->update($lineData);
                        }
                    } else {
                        $this->addLine($budget, $lineData);
                    }
                }
            }

            $this->recalculateTotals($budget);

            BudgetAuditLog::create([
                'company_id'  => $budget->company_id,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'updated',
                'before'      => $before,
                'after'       => $budget->fresh()->toArray(),
                'description' => "Budget {$budget->code} updated",
                'created_at'  => now(),
            ]);

            return $budget->fresh();
        });
    }

    /**
     * Submit a budget for approval.
     */
    public function submitForApproval(Budget $budget, int $userId): Budget
    {
        if (!$budget->isSubmittable()) {
            throw new \RuntimeException('Only draft budgets can be submitted for approval.');
        }

        return DB::transaction(function () use ($budget, $userId) {
            $budget->update(['status' => 'pending_approval']);

            BudgetAuditLog::create([
                'company_id'  => $budget->company_id,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'submitted',
                'after'       => ['status' => 'pending_approval'],
                'description' => "Budget {$budget->code} submitted for approval",
                'created_at'  => now(),
            ]);

            return $budget->fresh();
        });
    }

    /**
     * Approve a budget at a given chain level.
     */
    public function approve(Budget $budget, int $userId, ?string $comment = null): Budget
    {
        if ($budget->status !== 'pending_approval') {
            throw new \RuntimeException('Only budgets pending approval can be approved.');
        }

        return DB::transaction(function () use ($budget, $userId, $comment) {
            $chain = $budget->approval_chain ?? [];
            $currentLevel = collect($chain)->firstWhere('status', 'pending');

            if ($currentLevel) {
                $currentLevel['status'] = 'approved';
                $currentLevel['approver_id'] = $userId;
                $currentLevel['comment'] = $comment;
                $currentLevel['at'] = now()->toIso8601String();
            }

            // Check if all levels are approved
            $allApproved = collect($chain)->every(fn($step) => $step['status'] === 'approved');
            $newStatus = $allApproved ? 'approved' : 'pending_approval';

            $budget->update([
                'status'         => $newStatus,
                'approval_chain' => $chain,
                'approved_by'    => $allApproved ? $userId : null,
                'approved_at'    => $allApproved ? now() : null,
            ]);

            BudgetAuditLog::create([
                'company_id'  => $budget->company_id,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'approved',
                'after'       => ['status' => $newStatus, 'level' => $currentLevel['label'] ?? null],
                'description' => "Budget {$budget->code} approved at level: " . ($currentLevel['label'] ?? 'final'),
                'created_at'  => now(),
            ]);

            return $budget->fresh();
        });
    }

    /**
     * Reject a budget at a given chain level.
     */
    public function reject(Budget $budget, int $userId, string $reason): Budget
    {
        if ($budget->status !== 'pending_approval') {
            throw new \RuntimeException('Only budgets pending approval can be rejected.');
        }

        return DB::transaction(function () use ($budget, $userId, $reason) {
            $chain = $budget->approval_chain ?? [];
            $currentLevel = collect($chain)->firstWhere('status', 'pending');

            if ($currentLevel) {
                $currentLevel['status'] = 'rejected';
                $currentLevel['approver_id'] = $userId;
                $currentLevel['comment'] = $reason;
                $currentLevel['at'] = now()->toIso8601String();
            }

            $budget->update([
                'status'           => 'rejected',
                'approval_chain'   => $chain,
                'rejection_reason' => $reason,
            ]);

            BudgetAuditLog::create([
                'company_id'  => $budget->company_id,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'rejected',
                'before'      => ['status' => 'pending_approval'],
                'after'       => ['status' => 'rejected'],
                'description' => "Budget {$budget->code} rejected: {$reason}",
                'created_at'  => now(),
            ]);

            return $budget->fresh();
        });
    }

    /**
     * Lock an approved budget (make immutable).
     */
    public function lock(Budget $budget, int $userId): Budget
    {
        if (!$budget->isLockable()) {
            throw new \RuntimeException('Only approved budgets can be locked.');
        }

        return DB::transaction(function () use ($budget, $userId) {
            $budget->update([
                'status'    => 'locked',
                'locked_by' => $userId,
                'locked_at' => now(),
            ]);

            BudgetAuditLog::create([
                'company_id'  => $budget->company_id,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'locked',
                'before'      => ['status' => 'approved'],
                'after'       => ['status' => 'locked'],
                'description' => "Budget {$budget->code} locked",
                'created_at'  => now(),
            ]);

            return $budget->fresh();
        });
    }

    /**
     * Unlock a locked budget.
     */
    public function unlock(Budget $budget, int $userId): Budget
    {
        if ($budget->status !== 'locked') {
            throw new \RuntimeException('Only locked budgets can be unlocked.');
        }

        return DB::transaction(function () use ($budget, $userId) {
            $budget->update([
                'status'    => 'approved',
                'locked_by' => null,
                'locked_at' => null,
            ]);

            BudgetAuditLog::create([
                'company_id'  => $budget->company_id,
                'budget_id'   => $budget->id,
                'user_id'     => $userId,
                'action'      => 'unlocked',
                'before'      => ['status' => 'locked'],
                'after'       => ['status' => 'approved'],
                'description' => "Budget {$budget->code} unlocked",
                'created_at'  => now(),
            ]);

            return $budget->fresh();
        });
    }

    /**
     * Create a budget adjustment request.
     */
    public function createAdjustment(array $data, int $userId): BudgetAdjustment
    {
        $budget = Budget::findOrFail($data['budget_id']);

        $code = $this->nextAdjustmentCode($budget->company_id);

        $adjustment = BudgetAdjustment::create([
            'company_id'      => $budget->company_id,
            'budget_id'       => $budget->id,
            'budget_line_id'  => $data['budget_line_id'] ?? null,
            'code'            => $code,
            'type'            => $data['type'],
            'from_line_id'    => $data['from_line_id'] ?? null,
            'to_line_id'      => $data['to_line_id'] ?? null,
            'amount'          => $data['amount'],
            'reason'          => $data['reason'],
            'status'          => 'pending',
            'requested_by'    => $userId,
            'original_amount' => $data['original_amount'] ?? null,
        ]);

        BudgetAuditLog::create([
            'company_id'  => $budget->company_id,
            'budget_id'   => $budget->id,
            'user_id'     => $userId,
            'action'      => 'adjustment',
            'after'       => $adjustment->toArray(),
            'description' => "Adjustment {$code} created ({$adjustment->typeLabel()})",
            'created_at'  => now(),
        ]);

        return $adjustment;
    }

    /**
     * Approve a budget adjustment.
     */
    public function approveAdjustment(BudgetAdjustment $adjustment, int $userId, ?string $comment = null): BudgetAdjustment
    {
        if ($adjustment->status !== 'pending') {
            throw new \RuntimeException('Only pending adjustments can be approved.');
        }

        return DB::transaction(function () use ($adjustment, $userId, $comment) {
            $adjustment->update([
                'status'           => 'approved',
                'approved_by'      => $userId,
                'approved_at'      => now(),
                'approval_comment' => $comment,
            ]);

            // Apply the adjustment to the budget line
            if ($adjustment->budget_line_id && $adjustment->type !== 'transfer') {
                $line = BudgetLine::find($adjustment->budget_line_id);
                if ($line) {
                    $newAmount = match ($adjustment->type) {
                        'increase' => $line->annual_amount + $adjustment->amount,
                        'reduce'   => max(0, $line->annual_amount - $adjustment->amount),
                        default    => $line->annual_amount,
                    };
                    $line->update(['annual_amount' => $newAmount]);
                    $this->recalculateTotals($line->budget);
                }
            }

            // For transfers, reduce the source and increase the target
            if ($adjustment->type === 'transfer' && $adjustment->from_line_id && $adjustment->to_line_id) {
                $fromLine = BudgetLine::find($adjustment->from_line_id);
                $toLine = BudgetLine::find($adjustment->to_line_id);
                if ($fromLine && $toLine) {
                    $fromLine->update(['annual_amount' => max(0, $fromLine->annual_amount - $adjustment->amount)]);
                    $toLine->update(['annual_amount' => $toLine->annual_amount + $adjustment->amount]);
                    $this->recalculateTotals($fromLine->budget);
                }
            }

            BudgetAuditLog::create([
                'company_id'  => $adjustment->company_id,
                'budget_id'   => $adjustment->budget_id,
                'user_id'     => $userId,
                'action'      => 'adjustment',
                'after'       => ['status' => 'approved', 'code' => $adjustment->code],
                'description' => "Adjustment {$adjustment->code} approved",
                'created_at'  => now(),
            ]);

            return $adjustment->fresh();
        });
    }

    /**
     * Reject a budget adjustment.
     */
    public function rejectAdjustment(BudgetAdjustment $adjustment, int $userId, string $reason): BudgetAdjustment
    {
        if ($adjustment->status !== 'pending') {
            throw new \RuntimeException('Only pending adjustments can be rejected.');
        }

        $adjustment->update([
            'status'           => 'rejected',
            'approved_by'      => $userId,
            'approved_at'      => now(),
            'approval_comment' => $reason,
        ]);

        return $adjustment->fresh();
    }

    /**
     * Create a budget from a template.
     */
    public function createFromTemplate(BudgetTemplate $template, array $overrides, int $userId): Budget
    {
        $data = array_merge([
            'name'  => $template->name . ' (Copy)',
            'lines' => $template->template_data['lines'] ?? [],
        ], $overrides);

        return $this->create($data, $userId);
    }

    // ── Private helpers ────────────────────────────────────────

    private function addLine(Budget $budget, array $data): BudgetLine
    {
        $annualAmount = (float) ($data['annual_amount'] ?? 0);

        return BudgetLine::create([
            'company_id'         => $budget->company_id,
            'budget_id'          => $budget->id,
            'line_type'          => $data['line_type'] ?? 'expense',
            'account_id'         => $data['account_id'],
            'annual_amount'      => $annualAmount,
            'monthly_amount'     => $annualAmount / 12,
            'distribution'       => $data['distribution'] ?? 'even',
            'distribution_config' => $data['distribution_config'] ?? null,
            'department'         => $data['department'] ?? null,
            'branch_id'          => $data['branch_id'] ?? null,
            'project'            => $data['project'] ?? null,
            'cost_center_id'     => $data['cost_center_id'] ?? null,
        ]);
    }

    private function recalculateTotals(Budget $budget): void
    {
        $income = $budget->lines()->where('line_type', 'income')->sum('annual_amount');
        $expenses = $budget->lines()->where('line_type', 'expense')->sum('annual_amount');

        $budget->update([
            'total_income'   => $income,
            'total_expenses' => $expenses,
        ]);
    }

    private function nextCode(int $companyId): string
    {
        $lastBudget = Budget::where('company_id', $companyId)
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastBudget && preg_match('/BUD-(\d+)/', $lastBudget->code, $m)) {
            $nextNum = (int) $m[1] + 1;
        }

        return 'BUD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    private function nextAdjustmentCode(int $companyId): string
    {
        $lastAdj = BudgetAdjustment::where('company_id', $companyId)
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->first();

        $nextNum = 1;
        if ($lastAdj && preg_match('/ADJ-(\d+)/', $lastAdj->code, $m)) {
            $nextNum = (int) $m[1] + 1;
        }

        return 'ADJ-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
