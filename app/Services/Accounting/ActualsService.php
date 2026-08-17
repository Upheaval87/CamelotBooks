<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes actuals live from the GL — never stored in budget tables.
 * §19.1: Actuals = live GL sums per mapped account/department/cost-centre/branch/project.
 */
class ActualsService
{
    /**
     * Get actual amounts for a budget line, grouped by month.
     * Returns array of 12 monthly actuals.
     */
    public function monthlyActuals(BudgetLine $line, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $budget = $line->budget;
        $fy = FiscalYear::find($budget->fiscal_year_id);

        if (!$dateFrom) {
            $dateFrom = $fy?->start_date ?? now()->startOfYear()->toDateString();
        }
        if (!$dateTo) {
            $dateTo = $fy?->end_date ?? now()->endOfYear()->toDateString();
        }

        $start = Carbon::parse($dateFrom);
        $monthly = [];

        for ($m = 0; $m < 12; $m++) {
            $monthStart = $start->copy()->addMonths($m)->startOfMonth();
            $monthEnd = $start->copy()->addMonths($m)->endOfMonth();

            if ($monthEnd->toDateString() < $dateFrom || $monthStart->toDateString() > $dateTo) {
                $monthly[] = 0;
                continue;
            }

            $actual = $this->sumActualsForLine(
                $budget->company_id,
                $line->account_id,
                $monthStart->toDateString(),
                min($monthEnd->toDateString(), $dateTo),
                $line->department,
                $line->branch_id,
                $line->project,
                $line->cost_center_id
            );

            $monthly[] = $actual;
        }

        return $monthly;
    }

    /**
     * Get the annual actual for a budget line.
     */
    public function annualActual(BudgetLine $line, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return array_sum($this->monthlyActuals($line, $dateFrom, $dateTo));
    }

    /**
     * Get budget vs actual data for a full budget.
     * Returns per-line: budget, actual, remaining, utilization %.
     */
    public function budgetVsActual(Budget $budget): array
    {
        $lines = $budget->lines()->with('account')->get();

        $result = [
            'lines'             => [],
            'total_budgeted'    => 0,
            'total_actual'      => 0,
            'total_remaining'   => 0,
            'overall_utilization' => 0,
        ];

        foreach ($lines as $line) {
            $actual = $this->annualActual($line);
            $budgeted = (float) $line->annual_amount;
            $remaining = $budgeted - $actual;

            // Utilization: for expenses, actual/budgeted; for income, budgeted/actual (inverted)
            $utilization = $budgeted > 0 ? ($actual / $budgeted) * 100 : 0;

            $result['lines'][] = [
                'id'            => $line->id,
                'line_type'     => $line->line_type,
                'account_id'    => $line->account_id,
                'account_code'  => $line->account?->code,
                'account_name'  => $line->account?->name,
                'department'    => $line->department,
                'branch_id'     => $line->branch_id,
                'project'       => $line->project,
                'budgeted'      => $budgeted,
                'actual'        => $actual,
                'remaining'     => $remaining,
                'utilization'   => round($utilization, 1),
                'is_over'       => $line->line_type === 'expense' ? $actual > $budgeted : $actual < $budgeted,
                'is_nearing'    => $utilization >= 85 && $utilization < 100,
            ];

            $result['total_budgeted'] += $budgeted;
            $result['total_actual'] += $actual;
            $result['total_remaining'] += $remaining;
        }

        $result['overall_utilization'] = $result['total_budgeted'] > 0
            ? round(($result['total_actual'] / $result['total_budgeted']) * 100, 1)
            : 0;

        return $result;
    }

    /**
     * Dashboard KPI data across all budgets in a fiscal year.
     */
    public function dashboardKpis(int $companyId, int $fiscalYearId): array
    {
        $budgets = Budget::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->withCount('lines')
            ->get();

        $totalBudgeted = $budgets->sum('total_expenses');
        $totalIncome = $budgets->sum('total_income');

        // Compute actuals for all expense lines across all budgets
        $totalActual = 0;
        foreach ($budgets as $budget) {
            foreach ($budget->lines()->where('line_type', 'expense')->get() as $line) {
                $totalActual += $this->annualActual($line);
            }
        }

        $utilization = $totalBudgeted > 0 ? round(($totalActual / $totalBudgeted) * 100, 1) : 0;

        return [
            'total_budgets'       => $budgets->count(),
            'approved_budgets'    => $budgets->where('status', 'approved')->count(),
            'draft_budgets'       => $budgets->where('status', 'draft')->count(),
            'pending_budgets'     => $budgets->where('status', 'pending_approval')->count(),
            'total_income'        => $totalIncome,
            'total_budgeted'      => $totalBudgeted,
            'total_actual'        => $totalActual,
            'total_remaining'     => $totalBudgeted - $totalActual,
            'utilization'         => $utilization,
            'over_budget_count'   => $budgets->filter(fn($b) => $b->total_expenses > 0 && $b->status === 'approved')->count(),
        ];
    }

    /**
     * Get recent GL transactions for a budget line (for the Activity tab).
     */
    public function recentTransactions(BudgetLine $line, int $limit = 50): array
    {
        $budget = $line->budget;
        $fy = FiscalYear::find($budget->fiscal_year_id);

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.company_id', $budget->company_id)
            ->where('journal_entry_lines.account_id', $line->account_id)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '>=', $fy?->start_date ?? now()->startOfYear())
            ->where('journal_entries.date', '<=', $fy?->end_date ?? now()->endOfYear());

        if ($line->branch_id) {
            $query->where('journal_entry_lines.branch_id', $line->branch_id);
        }
        if ($line->cost_center_id) {
            $query->where('journal_entry_lines.cost_center_id', $line->cost_center_id);
        }

        return $query->orderByDesc('journal_entries.date')
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'date'        => $row->date,
                'reference'   => $row->reference ?? '',
                'description' => $row->memo ?? '',
                'debit'       => (float) $row->debit,
                'credit'      => (float) $row->credit,
                'amount'      => (float) $row->debit - (float) $row->credit,
            ])
            ->toArray();
    }

    /**
     * Raw GL sum for a specific account + period + optional dimensions.
     */
    private function sumActualsForLine(
        int $companyId,
        int $accountId,
        string $dateFrom,
        string $dateTo,
        ?string $department,
        ?int $branchId,
        ?string $project,
        ?int $costCenterId
    ): float {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entry_lines.account_id', $accountId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->whereBetween('journal_entries.date', [$dateFrom, $dateTo]);

        if ($branchId) {
            $query->where('journal_entry_lines.branch_id', $branchId);
        }
        if ($costCenterId) {
            $query->where('journal_entry_lines.cost_center_id', $costCenterId);
        }

        $row = $query->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        if (!$row) {
            return 0;
        }

        // For income accounts: credit is positive (actuals are credits)
        // For expense accounts: debit is positive (actuals are debits)
        $account = Account::find($accountId);
        if ($account && $account->type === 'income') {
            return (float) $row->total_credit;
        }

        return (float) $row->total_debit;
    }
}
