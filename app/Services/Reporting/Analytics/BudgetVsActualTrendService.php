<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Budget;
use App\Models\FiscalYear;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BudgetVsActualTrendService
{
    public function calculate(int $companyId, ?int $fiscalYearId = null, ?int $branchId = null): array
    {
        if (!$fiscalYearId) {
            $fy = FiscalYear::where('company_id', $companyId)->orderByDesc('start_date')->first();
            if (!$fy) {
                return ['error' => 'No fiscal year found', 'labels' => [], 'budget_data' => [], 'actual_data' => [], 'variance_data' => []];
            }
            $fiscalYearId = $fy->id;
        }
        
        $fy = FiscalYear::find($fiscalYearId);
        $budget = Budget::where('company_id', $companyId)->where('fiscal_year_id', $fiscalYearId)->first();
        
        if (!$budget) {
            return ['error' => 'No budget found for this fiscal year', 'labels' => [], 'budget_data' => [], 'actual_data' => [], 'variance_data' => []];
        }
        
        $startDate = Carbon::parse($fy->start_date);
        $endDate = Carbon::parse($fy->end_date);
        
        // Generate monthly labels
        $labels = [];
        $budgetCumulative = [];
        $actualCumulative = [];
        $varianceCumulative = [];
        
        $totalBudget = 0;
        $totalActual = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $monthLabel = $current->format('M Y');
            $labels[] = $monthLabel;
            
            // Budget for this month (total budget / number of months)
            $totalBudgetAmount = (float) $budget->lines->sum('amount');
            $monthBudget = $totalBudgetAmount / max(1, $startDate->diffInMonths($endDate) + 1);
            $totalBudget += $monthBudget;
            $budgetCumulative[] = $totalBudget;
            
            // Actual for this month
            $monthEnd = $current->copy()->endOfMonth();
            if ($monthEnd->gt($endDate)) {
                $monthEnd = $endDate->copy();
            }
            
            $actual = $this->getActualForPeriod($companyId, $startDate->format('Y-m-d'), $monthEnd->format('Y-m-d'), $branchId);
            $totalActual = $actual;
            $actualCumulative[] = $totalActual;
            
            $varianceCumulative[] = $totalBudget - $totalActual;
            
            $current->addMonth();
        }
        
        return [
            'labels' => $labels,
            'budget_data' => $budgetCumulative,
            'actual_data' => $actualCumulative,
            'variance_data' => $varianceCumulative,
            'total_budget' => $totalBudget,
            'total_actual' => $totalActual,
            'fiscal_year' => [
                'id' => $fy->id,
                'start_date' => $fy->start_date,
                'end_date' => $fy->end_date,
            ],
        ];
    }

    private function getActualForPeriod(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): float
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '<=', $dateTo)
            ->whereIn('accounts.type', ['income', 'expense']);
        
        if ($branchId) {
            $query->where('journal_entry_lines.branch_id', $branchId);
        }
        
        $result = $query->selectRaw('
            SUM(CASE WHEN accounts.type = \'income\' THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END) -
            SUM(CASE WHEN accounts.type = \'expense\' THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END) as net_income
        ')->value('net_income') ?? 0;
        
        return (float) $result;
    }
}
