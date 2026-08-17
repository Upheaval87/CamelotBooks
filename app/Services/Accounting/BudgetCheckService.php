<?php

namespace App\Services\Accounting;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

/**
 * §19.3 — Spending-control hook.
 * Called by ExpenseService (and other posting handlers) to check whether
 * a proposed posting would push any mapped budget line past its threshold.
 * Returning a permissive default keeps existing expense flow unbroken
 * when no budget mapping exists.
 */
class BudgetCheckService
{
    public function __construct(
        private ActualsService $actualsService = new ActualsService()
    ) {}

    /**
     * Check proposed posting lines against active budgets.
     *
     * @param int $companyId
     * @param array $lines  [{account_id, amount, department?, branch_id?, project?, cost_center_id?}]
     * @param string|null $date
     * @return array{status: string, accounts: array, warnings: array}
     */
    public function check(int $companyId, array $lines, ?string $date = null): array
    {
        // Find active budgets for this company that cover the relevant accounts
        $accountIds = array_column($lines, 'account_id');
        if (empty($accountIds)) {
            return ['status' => 'no_budget', 'accounts' => [], 'warnings' => []];
        }

        $activeBudgets = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->get();

        if ($activeBudgets->isEmpty()) {
            return ['status' => 'no_budget', 'accounts' => [], 'warnings' => []];
        }

        $warnings = [];
        $hasExceeded = false;
        $hasWarning = false;

        foreach ($activeBudgets as $budget) {
            $budgetLines = $budget->lines()
                ->whereIn('account_id', $accountIds)
                ->get();

            foreach ($budgetLines as $budgetLine) {
                // Find the matching proposed line
                $proposed = collect($lines)->firstWhere('account_id', $budgetLine->account_id);
                if (!$proposed) {
                    continue;
                }

                $actual = $this->actualsService->annualActual($budgetLine);
                $budgeted = (float) $budgetLine->annual_amount;
                if ($budgeted <= 0) {
                    continue;
                }

                $proposedAmount = (float) ($proposed['amount'] ?? 0);
                $projectedUtilization = (($actual + $proposedAmount) / $budgeted) * 100;

                if ($projectedUtilization >= 100) {
                    $hasExceeded = true;
                    $warnings[] = [
                        'budget_id'      => $budget->id,
                        'budget_line_id' => $budgetLine->id,
                        'account_id'     => $budgetLine->account_id,
                        'threshold'      => 'exceeded',
                        'utilization'    => round($projectedUtilization, 1),
                        'message'        => "Posting would push account {$budgetLine->account?->code} to {$projectedUtilization}% of budget.",
                    ];
                } elseif ($projectedUtilization >= 85) {
                    $hasWarning = true;
                    $warnings[] = [
                        'budget_id'      => $budget->id,
                        'budget_line_id' => $budgetLine->id,
                        'account_id'     => $budgetLine->account_id,
                        'threshold'      => 'nearing',
                        'utilization'    => round($projectedUtilization, 1),
                        'message'        => "Posting would push account {$budgetLine->account?->code} to {$projectedUtilization}% of budget.",
                    ];
                }
            }
        }

        $status = match (true) {
            $hasExceeded => 'exceeded',
            $hasWarning  => 'warning',
            default      => 'ok',
        };

        return ['status' => $status, 'accounts' => $accountIds, 'warnings' => $warnings];
    }

    /**
     * Summarize spending vs budget for a company.
     */
    public function spendVsBudget(int $companyId, ?int $fiscalYearId = null): array
    {
        $query = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked']);

        if ($fiscalYearId) {
            $query->where('fiscal_year_id', $fiscalYearId);
        }

        $budgets = $query->get();

        $totalBudget = $budgets->sum('total_expenses');
        $totalSpent = 0;

        $accounts = [];
        foreach ($budgets as $budget) {
            foreach ($budget->lines()->where('line_type', 'expense')->get() as $line) {
                $actual = $this->actualsService->annualActual($line);
                $totalSpent += $actual;

                $accounts[] = [
                    'account_id' => $line->account_id,
                    'account_code' => $line->account?->code,
                    'account_name' => $line->account?->name,
                    'budgeted' => (float) $line->annual_amount,
                    'spent' => $actual,
                    'utilization' => $line->annual_amount > 0
                        ? round(($actual / $line->annual_amount) * 100, 1)
                        : 0,
                ];
            }
        }

        return [
            'total_budget' => $totalBudget,
            'total_spent'  => $totalSpent,
            'accounts'     => $accounts,
        ];
    }
}
