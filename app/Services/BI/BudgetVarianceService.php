<?php

namespace App\Services\BI;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Services\BI\Concerns\BiPeriodMetrics;
use Illuminate\Support\Facades\DB;

class BudgetVarianceService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null, ?int $costCenterId = null): array
    {
        $budgetLines = BudgetLine::where('budget_lines.company_id', $companyId)
            ->where('budget_lines.line_type', 'expense')
            ->join('budgets', 'budget_lines.budget_id', '=', 'budgets.id')
            ->whereIn('budgets.status', ['approved', 'locked'])
            ->get([
                'budgets.id as budget_id',
                'budgets.name as budget_name',
                'budgets.period as budget_period',
                'budget_lines.account_id',
                'budget_lines.annual_amount',
                'budget_lines.monthly_amount',
            ]);

        if ($budgetLines->isEmpty()) {
            return [
                'rows' => [],
                'budget_total' => 0,
                'actual_total' => 0,
                'variance_total' => 0,
                'no_budget' => true,
            ];
        }

        // Actual spend per expense account over the range.
        $expenseRows = $this->expenseByAccount($companyId, $dateFrom, $dateTo, $branchId, $costCenterId);
        $actualByAccount = [];
        foreach ($expenseRows as $row) {
            $actualByAccount[(int) $row['account_id']] = (float) $row['amount'];
        }

        $factor = $this->rangeFactor($dateFrom, $dateTo);

        $months = [];
        $start = \Carbon\Carbon::parse($dateFrom)->startOfMonth();
        $end = \Carbon\Carbon::parse($dateTo)->endOfMonth();
        for ($m = $start->copy(); $m->lte($end); $m->addMonth()) {
            $months[$m->month - 1] = true;
        }

        $rows = [];
        $budgetTotal = 0.0;
        $actualTotal = 0.0;
        foreach ($budgetLines as $line) {
            $budgeted = (float) $line->monthly_amount > 0
                ? (float) $line->monthly_amount * count($months)
                : round((float) $line->annual_amount * $factor, 2);

            $actual = $actualByAccount[(int) $line->account_id] ?? round($this->accountActual($companyId, (int) $line->account_id, $dateFrom, $dateTo, $branchId, $costCenterId), 2);

            $variance = $actual - $budgeted;
            $rows[] = [
                'account_id' => (int) $line->account_id,
                'account_name' => \App\Models\Account::where('company_id', $companyId)->where('id', (int) $line->account_id)->value('name') ?? "Account #{$line->account_id}",
                'account_code' => \App\Models\Account::where('company_id', $companyId)->where('id', (int) $line->account_id)->value('code') ?? '',
                'budget_name' => $line->budget_name,
                'budgeted' => $budgeted,
                'actual' => round($actual, 2),
                'variance' => round($variance, 2),
                'variance_pct' => $budgeted > 0 ? round($variance / $budgeted * 100, 1) : null,
            ];
            $budgetTotal += $budgeted;
            $actualTotal += $actual;
        }

        usort($rows, fn ($a, $b) => $b['actual'] <=> $a['actual']);

        return [
            'rows' => $rows,
            'budget_total' => round($budgetTotal, 2),
            'actual_total' => round($actualTotal, 2),
            'variance_total' => round($actualTotal - $budgetTotal, 2),
            'no_budget' => false,
        ];
    }

    protected function rangeFactor(string $dateFrom, string $dateTo): float
    {
        $from = \Carbon\Carbon::parse($dateFrom);
        $to = \Carbon\Carbon::parse($dateTo);
        $days = $from->diffInDays($to) + 1;
        return round($days / 365, 4);
    }

    protected function accountActual(int $companyId, int $accountId, string $dateFrom, string $dateTo, ?int $branchId, ?int $costCenterId): float
    {
        $row = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('journal_entry_lines.cost_center_id', $costCenterId))
            ->selectRaw('SUM(CASE WHEN accounts.normal_balance = "debit" THEN debit - credit ELSE credit - debit END) as amount')
            ->first();

        return (float) ($row->amount ?? 0);
    }
}