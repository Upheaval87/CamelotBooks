<?php

namespace App\Services\BI;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductAbcService
{
    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('costLayers')
            ->get(['id', 'name', 'sku', 'purchase_price', 'tracked_as_inventory']);

        $invoiceRows = InvoiceLine::selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['posted', 'paid', 'partially_paid'])
            ->where('invoices.invoice_date', '>=', $dateFrom)
            ->where('invoices.invoice_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $posRows = PosSaleLine::selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as revenue, SUM(cost_of_goods) as pos_cogs')
            ->join('pos_sales', 'pos_sale_lines.pos_sale_id', '=', 'pos_sales.id')
            ->where('pos_sales.company_id', $companyId)
            ->where('pos_sales.status', PosSale::STATUS_POSTED)
            ->whereBetween('pos_sales.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('pos_sales.branch_id', $branchId))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $rows = [];
        foreach ($products as $product) {
            $inv = $invoiceRows->get($product->id);
            $pos = $posRows->get($product->id);

            $invRevenue = (float) ($inv->revenue ?? 0);
            $posRevenue = (float) ($pos->revenue ?? 0);
            $invQty = (float) ($inv->qty ?? 0);
            $posQty = (float) ($pos->qty ?? 0);
            $posCogs = (float) ($pos->pos_cogs ?? 0);

            $revenue = $invRevenue + $posRevenue;
            if ($revenue <= 0 && $invQty <= 0 && $posQty <= 0) {
                continue;
            }

            $unitCost = $this->weightedUnitCost($product);
            $invCogs = $invQty * $unitCost;
            $cogs = $invCogs + $posCogs;
            $contribution = $revenue - $cogs;

            $rows[] = [
                'product_id' => (int) $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $invQty + $posQty,
                'revenue' => round($revenue, 2),
                'cogs' => round($cogs, 2),
                'contribution' => round($contribution, 2),
                'margin_pct' => $revenue > 0 ? round($contribution / $revenue * 100, 1) : null,
            ];
        }

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $total = array_sum(array_column($rows, 'revenue'));
        $cumulative = 0.0;
        foreach ($rows as &$row) {
            $cumulative += $row['revenue'];
            $row['pct_of_revenue'] = $total > 0 ? round($row['revenue'] / $total * 100, 1) : 0;
            $row['cummulative_pct'] = $total > 0 ? round($cumulative / $total * 100, 1) : 0;
            $row['class'] = $total > 0 ? $this->abcClass($row['cummulative_pct']) : 'C';
        }
        unset($row);

        return [
            'rows' => $rows,
            'total_revenue' => round($total, 2),
            'a_value' => round(array_sum(array_column(array_filter($rows, fn ($r) => $r['class'] === 'A'), 'revenue')), 2),
            'b_value' => round(array_sum(array_column(array_filter($rows, fn ($r) => $r['class'] === 'B'), 'revenue')), 2),
            'c_value' => round(array_sum(array_column(array_filter($rows, fn ($r) => $r['class'] === 'C'), 'revenue')), 2),
            'a_count' => count(array_filter($rows, fn ($r) => $r['class'] === 'A')),
            'b_count' => count(array_filter($rows, fn ($r) => $r['class'] === 'B')),
            'c_count' => count(array_filter($rows, fn ($r) => $r['class'] === 'C')),
        ];
    }

    private function weightedUnitCost(Product $product): float
    {
        $layers = $product->costLayers->where('quantity_remaining', '>', 0);
        if ($layers->isEmpty()) {
            return (float) $product->purchase_price;
        }
        $qty = 0.0;
        $value = 0.0;
        foreach ($layers as $layer) {
            $q = (float) $layer->quantity_remaining;
            $qty += $q;
            $value += $q * (float) $layer->unit_cost;
        }
        return $qty > 0 ? $value / $qty : (float) $product->purchase_price;
    }

    private function abcClass(float $cumulativePct): string
    {
        if ($cumulativePct <= 80) {
            return 'A';
        }
        if ($cumulativePct <= 95) {
            return 'B';
        }
        return 'C';
    }
}