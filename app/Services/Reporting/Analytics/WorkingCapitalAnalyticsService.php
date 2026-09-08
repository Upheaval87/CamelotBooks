<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\AgingReportService;
use App\Services\Reporting\IncomeStatementService;
use Carbon\Carbon;

class WorkingCapitalAnalyticsService
{
    public function calculate(int $companyId, array $period, ?int $branchId = null, ?int $costCenterId = null): array
    {
        [$dso, $dio, $dpo] = $this->cycles($companyId, $period, $branchId, $costCenterId);

        $agingAr = (new AgingReportService())->arAging($companyId, $branchId, $period['as_of']);
        $agingAp = (new AgingReportService())->apAging($companyId, $branchId, $period['as_of']);

        $arTotals = $agingAr['totals'] ?? [];
        $apTotals = $agingAp['totals'] ?? [];

        $arBuckets = [
            'current' => $arTotals['current'] ?? 0,
            'days_1_30' => $arTotals['days_1_30'] ?? 0,
            'days_31_60' => $arTotals['days_31_60'] ?? 0,
            'over_60' => ($arTotals['days_61_90'] ?? 0) + ($arTotals['days_90_plus'] ?? 0),
            'total' => $arTotals['total'] ?? 0,
        ];
        $apBuckets = [
            'current' => $apTotals['current'] ?? 0,
            'days_1_30' => $apTotals['days_1_30'] ?? 0,
            'days_31_60' => $apTotals['days_31_60'] ?? 0,
            'over_60' => ($apTotals['days_61_90'] ?? 0) + ($apTotals['days_90_plus'] ?? 0),
            'total' => $apTotals['total'] ?? 0,
        ];

        $ccc = null;
        if ($dso !== null && $dio !== null && $dpo !== null) {
            $ccc = $dso + $dio - $dpo;
        }

        return [
            'kpis' => [
                'dso' => ['value' => $dso],
                'dio' => ['value' => $dio],
                'dpo' => ['value' => $dpo],
                'ccc' => ['value' => $ccc],
                'net_position' => ['value' => $arBuckets['total'] - $apBuckets['total']],
            ],
            'ar' => $arBuckets,
            'ap' => $apBuckets,
        ];
    }

    private function cycles(int $companyId, array $period, ?int $branchId, ?int $costCenterId): array
    {
        $is = (new IncomeStatementService())->generate($companyId, $branchId, $period['from'], $period['to'], null, $costCenterId);
        $revenue = (float) ($is['total_income'] ?? 0);
        $expense = (float) ($is['total_expenses'] ?? 0);
        $cogs = $this->cogs($is);

        $ar = (new AgingReportService())->arAging($companyId, $branchId, $period['as_of']);
        $ap = (new AgingReportService())->apAging($companyId, $branchId, $period['as_of']);
        $totalAr = $ar['totals']['total'] ?? 0;
        $totalAp = $ap['totals']['total'] ?? 0;

        $days = Carbon::parse($period['from'])->diffInDays(Carbon::parse($period['to'])) + 1;

        $dso = $revenue > 0 ? ($totalAr / $revenue) * $days : null;
        $dpo = $expense > 0 ? ($totalAp / $expense) * $days : null;

        $inventory = $this->inventoryValue($companyId, $period['as_of']);
        $dio = $cogs > 0 ? ($inventory / $cogs) * $days : null;

        return [round($dso, 0), round($dio, 0), round($dpo, 0)];
    }

    private function cogs(array $is): float
    {
        $total = 0;
        foreach (($is['groups']['expense']['cost_of_goods_sold'] ?? []) as $item) {
            $total += abs($item['net'] ?? 0);
        }
        return $total;
    }

    private function inventoryValue(int $companyId, string $asOf): float
    {
        $valuation = app(\App\Services\Accounting\InventoryService::class)->getValuation($companyId, $asOf);
        return array_sum(array_column($valuation, 'total_value'));
    }
}