<?php

namespace App\Services\Reporting;

use App\Models\InvoiceLine;
use App\Models\PosSaleLine;
use App\Models\SalesReceiptLine;
use Illuminate\Support\Facades\DB;

class SalesByItemService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $invoiceLines = InvoiceLine::whereHas('invoice', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->forCompany($companyId)
                ->whereIn('status', ['sent', 'partially_paid', 'paid'])
                ->where('invoice_date', '>=', $dateFrom)
                ->where('invoice_date', '<=', $dateTo);
        })->select('product_id', DB::raw('SUM(line_total) as total'), DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->get();

        $receiptLines = SalesReceiptLine::whereHas('salesReceipt', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->forCompany($companyId)
                ->where('status', 'posted')
                ->where('receipt_date', '>=', $dateFrom)
                ->where('receipt_date', '<=', $dateTo);
        })->select('product_id', DB::raw('SUM(line_total) as total'), DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->get();

        $posLines = PosSaleLine::whereHas('sale', function ($q) use ($companyId) {
            $q->forCompany($companyId)->posted();
        })->select('product_id', DB::raw('SUM(line_total) as total'), DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->get();

        $allLines = $invoiceLines->concat($receiptLines)->concat($posLines);
        $grouped = $allLines->groupBy('product_id')->map(function ($lines, $pid) {
            return [
                'product_id' => (int) $pid,
                'total' => $lines->sum('total'),
                'qty' => $lines->sum('qty'),
                'count' => $lines->sum('count'),
            ];
        })->values();

        $productIds = $grouped->pluck('product_id')->filter();
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        $results = $grouped->map(function ($item) use ($products) {
            $item['product_name'] = $products[$item['product_id']]->name ?? 'N/A';
            $item['sku'] = $products[$item['product_id']]->sku ?? '';
            return $item;
        })->sortByDesc('total')->values()->toArray();

        return [
            'items' => $results,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
