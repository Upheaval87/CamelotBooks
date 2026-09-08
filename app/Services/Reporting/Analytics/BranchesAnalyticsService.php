<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Branch;
use App\Services\Reporting\Analytics\ProfitabilityAnalyticsService;

class BranchesAnalyticsService
{
    public function calculate(int $companyId, array $period, ?int $costCenterId = null): array
    {
        $service = new ProfitabilityAnalyticsService();

        $current = $service->calculate($companyId, $period['from'], $period['to']);
        $previous = $service->calculate($companyId, $period['prev_from'], $period['prev_to']);

        $rows = [];

        foreach (($current['by_branch'] ?? []) as $branchData) {
            $branchId = $branchData['branch_id'] ?? null;
            $branchName = $branchData['branch_name'] ?? ($branchId ? $this->branchName($branchId) : 'Head Office');

            $revenue = (float) $branchData['revenue'];
            $expense = (float) $branchData['expenses'];
            $profit = (float) $branchData['net_income'];
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            $prev = $this->findByBranch($previous['by_branch'] ?? [], $branchId);
            $prevRevenue = (float) ($prev['revenue'] ?? 0);
            $prevProfit = (float) ($prev['net_income'] ?? 0);
            $growth = $prevRevenue != 0 ? (($revenue - $prevRevenue) / abs($prevRevenue)) * 100 : null;

            $rows[] = [
                'branch_id' => $branchId,
                'name' => $branchName,
                'revenue' => $revenue,
                'expense' => $expense,
                'profit' => $profit,
                'margin' => $margin,
                'growth' => $growth,
                'profit_prev' => $prevProfit,
            ];
        }

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'rows' => $rows,
            'total_revenue' => array_sum(array_column($rows, 'revenue')),
            'total_profit' => array_sum(array_column($rows, 'profit')),
        ];
    }

    private function findByBranch(array $byBranch, ?int $branchId): ?array
    {
        foreach ($byBranch as $row) {
            if (($row['branch_id'] ?? null) === $branchId) {
                return $row;
            }
        }
        return null;
    }

    private function branchName(int $branchId): string
    {
        return Branch::find($branchId)?->name ?? 'Head Office';
    }
}