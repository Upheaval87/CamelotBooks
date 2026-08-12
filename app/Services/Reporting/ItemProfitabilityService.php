<?php
namespace App\Services\Reporting;
use App\Models\InvoiceLine;
use App\Models\PosSaleLine;
use App\Models\SalesReceiptLine;
use Illuminate\Support\Facades\DB;

class ItemProfitabilityService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        // Revenue from invoice lines
        $invRevenue = InvoiceLine::whereHas('invoice', fn($q) => $q->forCompany($companyId)->whereIn('status', ['sent', 'partially_paid', 'paid'])->where('invoice_date', '>=', $dateFrom)->where('invoice_date', '<=', $dateTo))
            ->select('product_id', DB::raw('SUM(line_total) as revenue'), DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_id')->get();

        // Revenue from receipt lines
        $recRevenue = SalesReceiptLine::whereHas('salesReceipt', fn($q) => $q->forCompany($companyId)->where('status', 'posted')->where('receipt_date', '>=', $dateFrom)->where('receipt_date', '<=', $dateTo))
            ->select('product_id', DB::raw('SUM(line_total) as revenue'), DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_id')->get();

        // COGS from POS lines
        $posCogs = PosSaleLine::whereHas('sale', fn($q) => $q->forCompany($companyId)->posted())
            ->select('product_id', DB::raw('SUM(cost_of_goods) as cogs'))
            ->groupBy('product_id')->get();

        $allRevenue = $invRevenue->concat($recRevenue)->groupBy('product_id')->map(fn($lines) => [
            'revenue' => $lines->sum('revenue'),
            'qty' => $lines->sum('qty'),
        ]);

        $cogsMap = $posCogs->keyBy('product_id');

        $allPids = $allRevenue->keys()->merge($cogsMap->keys())->unique()->filter();
        $products = \App\Models\Product::whereIn('id', $allPids)->get()->keyBy('id');

        $results = [];
        foreach ($allPids as $pid) {
            $rev = $allRevenue->get($pid, ['revenue' => 0, 'qty' => 0]);
            $cogs = $cogsMap->get($pid)?->cogs ?? 0;
            $profit = (float) $rev['revenue'] - (float) $cogs;
            $margin = $rev['revenue'] > 0 ? round(($profit / (float) $rev['revenue']) * 100, 1) : 0;

            $results[] = [
                'product_id' => $pid,
                'product_name' => $products[$pid]->name ?? 'N/A',
                'sku' => $products[$pid]->sku ?? '',
                'revenue' => (float) $rev['revenue'],
                'qty_sold' => (float) $rev['qty'],
                'cogs' => (float) $cogs,
                'profit' => $profit,
                'margin_pct' => $margin,
            ];
        }

        usort($results, fn($a, $b) => $b['profit'] <=> $a['profit']);
        return ['items' => $results, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
