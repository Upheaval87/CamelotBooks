<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Accounting\InventoryService;
use App\Models\InventoryCostLayer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class InventoryAnalyticsService
{
    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->inventoryService = app(InventoryService::class);
    }

    public function calculate(int $companyId, string $asOfDate, string $dateFrom, string $dateTo, int $slowMovingDays = 90): array
    {
        // Current stock value
        $currentValuation = $this->inventoryService->getValuation($companyId, $asOfDate);
        $totalStockValue = array_sum(array_column($currentValuation, 'total_value'));
        $totalQuantity = array_sum(array_column($currentValuation, 'total_quantity'));
        
        // Stock value trend (monthly snapshots)
        $valueTrend = $this->getValueTrend($companyId, $dateFrom, $dateTo);
        
        // Turnover analysis by product
        $turnover = $this->getTurnoverByProduct($companyId, $dateFrom, $dateTo);
        
        // Slow-moving / aging stock
        $slowMoving = $this->getSlowMovingStock($companyId, $asOfDate, $slowMovingDays);
        
        // Stockout frequency
        $stockouts = $this->getStockoutFrequency($companyId, $dateFrom, $dateTo);
        
        // Low stock items
        $lowStock = $this->inventoryService->getLowStockItems($companyId);
        
        return [
            'current_value' => [
                'total_value' => $totalStockValue,
                'total_quantity' => $totalQuantity,
                'item_count' => count($currentValuation),
            ],
            'valuation' => $currentValuation,
            'value_trend' => $valueTrend,
            'turnover' => $turnover,
            'slow_moving' => $slowMoving,
            'stockouts' => $stockouts,
            'low_stock' => $lowStock,
            'labels' => $valueTrend['labels'],
            'value_data' => $valueTrend['values'],
        ];
    }

    private function getValueTrend(int $companyId, string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfMonth();
        $end = Carbon::parse($dateTo);
        $labels = [];
        $values = [];
        
        while ($start->lte($end)) {
            $labels[] = $start->format('M Y');
            $valuation = $this->inventoryService->getValuation($companyId, $start->format('Y-m-d'));
            $values[] = array_sum(array_column($valuation, 'total_value'));
            $start->addMonth();
        }
        
        return ['labels' => $labels, 'values' => $values];
    }

    private function getTurnoverByProduct(int $companyId, string $dateFrom, string $dateTo): array
    {
        // COGS from journal entries for inventory-related expense accounts
        $cogsRows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', ['posted', 'reversed'])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->where('accounts.code', '6700') // Inventory Adjustment
            ->selectRaw('SUM(debit - credit) as cogs')
            ->value('cogs') ?? 0;
        
        $valuation = $this->inventoryService->getValuationByProduct($companyId);
        
        $results = [];
        foreach ($valuation as $item) {
            $avgValue = $item['total_value'];
            $turnover = $avgValue > 0 ? abs($cogsRows) / $avgValue : null;
            $daysOnHand = $turnover && $turnover > 0 ? 365 / $turnover : null;
            
            $results[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'sku' => $item['sku'],
                'avg_cost' => $item['avg_cost'],
                'total_value' => $item['total_value'],
                'total_quantity' => $item['total_quantity'],
                'turnover' => $turnover,
                'days_on_hand' => $daysOnHand,
            ];
        }
        
        usort($results, fn ($a, $b) => ($b['total_value'] ?? 0) <=> ($a['total_value'] ?? 0));
        
        return $results;
    }

    private function getSlowMovingStock(int $companyId, string $asOfDate, int $days): array
    {
        // Find cost layers older than $days that are still available
        $cutoffDate = Carbon::parse($asOfDate)->subDays($days)->format('Y-m-d');
        
        $layers = InventoryCostLayer::where('inventory_cost_layers.company_id', $companyId)
            ->available()
            ->where('inventory_cost_layers.created_at', '<=', $cutoffDate)
            ->join('products', 'inventory_cost_layers.product_id', '=', 'products.id')
            ->selectRaw('
                inventory_cost_layers.product_id,
                products.name as product_name,
                products.sku,
                SUM(inventory_cost_layers.quantity_remaining) as old_quantity,
                SUM(inventory_cost_layers.quantity_remaining * inventory_cost_layers.unit_cost) as old_value,
                MIN(inventory_cost_layers.created_at) as oldest_layer_date
            ')
            ->groupBy('inventory_cost_layers.product_id', 'products.name', 'products.sku')
            ->orderByDesc('old_value')
            ->get();
        
        return $layers->toArray();
    }

    private function getStockoutFrequency(int $companyId, string $dateFrom, string $dateTo): array
    {
        if (!Schema::hasTable('stock_adjustments')) {
            return [];
        }

        // Count stock adjustments that reduced stock to zero or below (simplified)
        $adjustments = DB::table('stock_adjustments')
            ->where('stock_adjustments.company_id', $companyId)
            ->where('type', 'subtraction')
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->selectRaw('product_id, COUNT(*) as adjustment_count, SUM(ABS(quantity)) as total_adjusted')
            ->groupBy('product_id')
            ->get();
        
        $results = [];
        foreach ($adjustments as $adj) {
            $product = Product::find($adj->product_id);
            $results[] = [
                'product_id' => $adj->product_id,
                'product_name' => $product?->name ?? 'Unknown',
                'sku' => $product?->sku ?? '',
                'adjustment_count' => $adj->adjustment_count,
                'total_adjusted' => $adj->total_adjusted,
            ];
        }
        
        return $results;
    }
}
