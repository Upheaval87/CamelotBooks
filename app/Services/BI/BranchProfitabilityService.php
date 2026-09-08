<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\BiPeriodMetrics;

class BranchProfitabilityService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $rows = $this->glRows($companyId, $dateFrom, $dateTo, $branchId, null, false, true);

        $byBranch = [];
        foreach ($rows as $row) {
            $bid = (int) $row->branch_id;
            if (!isset($byBranch[$bid])) {
                $byBranch[$bid] = [
                    'branch_id' => $bid,
                    'branch_name' => $row->branch_name,
                    'rows' => collect(),
                ];
            }
            $byBranch[$bid]['rows']->push($row);
        }

        $branches = [];
        foreach ($byBranch as $bid => $entry) {
            $totals = $this->classifyTotals($entry['rows']);

            $grossProfit = $totals['gross_profit'];
            $totalExpenses = $totals['total_expenses'];
            $netIncome = $totals['net_income'];
            $revenue = $totals['revenue'];

            $branches[] = [
                'branch_id' => $bid,
                'branch_name' => $entry['branch_name'],
                'revenue' => $revenue,
                'cogs' => $totals['cogs'],
                'gross_profit' => $grossProfit,
                'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
                'opex' => $totals['opex'],
                'payroll' => $totals['payroll'],
                'depreciation' => $totals['depreciation'],
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'net_margin' => $revenue > 0 ? ($netIncome / $revenue) * 100 : 0,
            ];
        }

        // Always include every active branch (zero rows for untouched branches).
        $activeBranches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($activeBranches as $branch) {
            if (!isset($byBranch[(int) $branch->id])) {
                $branches[] = [
                    'branch_id' => (int) $branch->id,
                    'branch_name' => $branch->name,
                    'revenue' => 0.0,
                    'cogs' => 0.0,
                    'gross_profit' => 0.0,
                    'gross_margin' => 0,
                    'opex' => 0.0,
                    'payroll' => 0.0,
                    'depreciation' => 0.0,
                    'total_expenses' => 0.0,
                    'net_income' => 0.0,
                    'net_margin' => 0,
                ];
            }
        }

        usort($branches, fn ($a, $b) => $b['net_income'] <=> $a['net_income']);

        return [
            'branches' => $branches,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}