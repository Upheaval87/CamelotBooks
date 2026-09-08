<?php

namespace App\Services\BI;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Product;
use Carbon\Carbon;

class PriceVolumeMixService
{
    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $prevTo = Carbon::parse($dateFrom)->subDay();
        $prevFrom = $prevTo->copy()->subDays(Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)));

        $current = $this->productRevenue($companyId, $dateFrom, $dateTo, $branchId);
        $previous = $this->productRevenue($companyId, $prevFrom->toDateString(), $prevTo->toDateString(), $branchId);

        $products = Product::where('company_id', $companyId)->get(['id', 'name', 'sku'])->keyBy('id');

        $curRevenue = 0.0;
        $priRevenue = 0.0;
        $priceEffect = 0.0;
        $volumeEffect = 0.0;
        $mixEffect = 0.0;
        $rows = [];

        foreach ($current as $pid => $cur) {
            $pri = $previous[$pid] ?? null;
            $name = $products->get($pid)->name ?? "Product #{$pid}";
            $sku = $products->get($pid)->sku ?? '';

            $q0 = (float) ($pri->qty ?? 0);
            $q1 = (float) $cur->qty;
            $r0 = (float) ($pri->revenue ?? 0);
            $r1 = (float) $cur->revenue;
            $p0 = $q0 > 0 ? $r0 / $q0 : ($q1 > 0 ? $r1 / $q1 : 0);
            $p1 = $q1 > 0 ? $r1 / $q1 : $p0;

            $rows[$pid] = [
                'product_id' => (int) $pid,
                'product_name' => $name,
                'sku' => $sku,
                'prev_qty' => round($q0, 2),
                'prev_revenue' => round($r0, 2),
                'cur_qty' => round($q1, 2),
                'cur_revenue' => round($r1, 2),
                'price' => round($p1, 2),
                'prev_price' => round($p0, 2),
            ];

            $curRevenue += $r1;
            $priRevenue += $r0;

            // Unexplained residual (new products, tax changes) lands in mix.
            $priceEffect += ($p1 - $p0) * $q1;
            $volumeEffect += ($q1 - $q0) * $p0;
        }
        $mixEffect = ($curRevenue - $priRevenue) - $priceEffect - $volumeEffect;

        usort($rows, fn ($a, $b) => $b['cur_revenue'] <=> $a['cur_revenue']);

        return [
            'rows' => array_values($rows),
            'current_revenue' => round($curRevenue, 2),
            'previous_revenue' => round($priRevenue, 2),
            'total_change' => round($curRevenue - $priRevenue, 2),
            'price_effect' => round($priceEffect, 2),
            'volume_effect' => round($volumeEffect, 2),
            'mix_effect' => round($mixEffect, 2),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    protected function productRevenue(int $companyId, string $dateFrom, string $dateTo, ?int $branchId)
    {
        $invoice = InvoiceLine::selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['posted', 'paid', 'partially_paid'])
            ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
            ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get();

        $pos = PosSaleLine::selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->join('pos_sales', 'pos_sale_lines.pos_sale_id', '=', 'pos_sales.id')
            ->where('pos_sales.company_id', $companyId)
            ->where('pos_sales.status', PosSale::STATUS_POSTED)
            ->whereBetween('pos_sales.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('pos_sales.branch_id', $branchId))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get();

        $merged = [];
        foreach ($invoice as $row) {
            $merged[(int) $row->product_id] = ['qty' => (float) $row->qty, 'revenue' => (float) $row->revenue];
        }
        foreach ($pos as $row) {
            $pid = (int) $row->product_id;
            $merged[$pid] = [
                'qty' => ($merged[$pid]['qty'] ?? 0) + (float) $row->qty,
                'revenue' => ($merged[$pid]['revenue'] ?? 0) + (float) $row->revenue,
            ];
        }

        return collect($merged)->map(function ($r) {
            return (object) $r;
        });
    }
}