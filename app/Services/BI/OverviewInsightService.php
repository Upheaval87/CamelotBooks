<?php

namespace App\Services\BI;

use App\Models\InventoryCostLayer;
use App\Models\Product;
use App\Services\BI\Concerns\BiPeriodMetrics;
use App\Services\Reporting\AgingReportService;
use Illuminate\Support\Facades\DB;

class OverviewInsightService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null, ?int $costCenterId = null): array
    {
        $current = $this->statementTotals($companyId, $dateFrom, $dateTo, $branchId, $costCenterId);

        $prevFrom = \Carbon\Carbon::parse($dateFrom);
        $prevTo = \Carbon\Carbon::parse($dateTo);
        $length = $prevFrom->diffInDays($prevTo) + 1;
        $ppTo = $prevFrom->copy()->subDay();
        $ppFrom = $ppTo->copy()->subDays($length - 1);
        $previous = $this->statementTotals($companyId, $ppFrom->toDateString(), $ppTo->toDateString(), $branchId, $costCenterId);

        $aging = new AgingReportService();
        $ar = $aging->arAging($companyId, $branchId, $dateTo);
        $ap = $aging->apAging($companyId, $branchId, $dateTo);

        $cash = $this->cashAndBankAsOf($companyId, $dateTo);
        $inventory = $this->inventoryValueAsOf($companyId, $dateTo);

        $trend = $this->monthlyTrend($companyId, $dateTo, $branchId, $costCenterId);

        return [
            'kpis' => [
                'revenue' => ['value' => $current['revenue'], 'prev' => $previous['revenue']],
                'gross_profit' => ['value' => $current['gross_profit'], 'prev' => $previous['gross_profit']],
                'net_income' => ['value' => $current['net_income'], 'prev' => $previous['net_income']],
                'cash_balance' => ['value' => $cash['total'], 'prev' => null],
                'ar_outstanding' => ['value' => $ar['totals']['total'], 'prev' => null],
                'ap_outstanding' => ['value' => $ap['totals']['total'], 'prev' => null],
                'inventory_value' => ['value' => $inventory, 'prev' => null],
            ],
            'statement' => $current,
            'previous' => $previous,
            'cash' => $cash,
            'ar' => $ar['totals'],
            'ap' => $ap['totals'],
            'inventory_value' => $inventory,
            'trend' => $trend,
            'alerts' => $this->buildAlerts($companyId, $ar['totals']),
        ];
    }

    protected function inventoryValueAsOf(int $companyId, string $asOf): float
    {
        return (float) InventoryCostLayer::where('company_id', $companyId)
            ->where('date', '<=', $asOf)
            ->where('quantity_remaining', '>', 0)
            ->sum(DB::raw('quantity_remaining * unit_cost'));
    }

    public function monthlyTrend(int $companyId, string $asOf, ?int $branchId, ?int $costCenterId): array
    {
        $labels = [];
        $revenue = [];
        $expense = [];

        $cursor = \Carbon\Carbon::parse($asOf)->startOfMonth();
        for ($i = 0; $i < 6; $i++) {
            $from = $cursor->copy()->subMonths($i)->startOfMonth();
            $to = $cursor->copy()->subMonths($i)->endOfMonth();
            if ($to->greaterThan(\Carbon\Carbon::parse($asOf))) {
                $to = \Carbon\Carbon::parse($asOf);
            }
            $t = $this->statementTotals($companyId, $from->toDateString(), $to->toDateString(), $branchId, $costCenterId);
            array_unshift($labels, $from->format('M'));
            array_unshift($revenue, round($t['revenue'], 2));
            array_unshift($expense, round($t['total_expenses'], 2));
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expense' => $expense,
        ];
    }

    protected function buildAlerts(int $companyId, array $arTotals): array
    {
        $alerts = [];

        $lowStock = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('tracked_as_inventory', true)
            ->get(['id', 'name', 'sku']);

        // Allow existing helper path for low-stock threshold (qty <= reorder if set, else <= 0).
        $lowStockRows = [];
        foreach ($lowStock as $product) {
            $onHand = (float) $product->stock()->sum('quantity_on_hand');
            $reorder = (float) ($product->effective_reorder_point ?? 0);
            if ($onHand <= $reorder) {
                $lowStockRows[] = [
                    'product_id' => (int) $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'on_hand' => $onHand,
                ];
            }
        }
        if ($lowStockRows) {
            $alerts[] = ['level' => 'warn', 'label' => 'Low stock', 'detail' => count($lowStockRows) . ' product(s) at or below reorder level.'];
        }

        if (($arTotals['days_31_60'] ?? 0) + ($arTotals['days_61_90'] ?? 0) + ($arTotals['days_90_plus'] ?? 0) > 0) {
            $alerts[] = ['level' => 'warn', 'label' => 'Aged receivables', 'detail' => $this->money($arTotals['days_31_60'] + $arTotals['days_61_90'] + $arTotals['days_90_plus']) . ' of receivables are 30+ days overdue.'];
        }

        return $alerts;
    }

    protected function money(float $value): string
    {
        return number_format($value, 2);
    }
}