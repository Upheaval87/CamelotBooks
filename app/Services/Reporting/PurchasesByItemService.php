<?php

namespace App\Services\Reporting;

use App\Models\BillLine;
use App\Models\ExpenseLine;
use Illuminate\Support\Facades\DB;

class PurchasesByItemService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $billLines = BillLine::whereHas('bill', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->forCompany($companyId)->whereIn('status', ['posted', 'partially_paid', 'paid'])
                ->where('bill_date', '>=', $dateFrom)->where('bill_date', '<=', $dateTo);
        })->select('product_id', DB::raw('SUM(line_total) as total'), DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')->get();

        $expenseLines = ExpenseLine::whereHas('expense', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->forCompany($companyId)->where('status', 'posted')
                ->where('expense_date', '>=', $dateFrom)->where('expense_date', '<=', $dateTo);
        })->select('product_id', DB::raw('SUM(line_total) as total'), DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')->get();

        $merged = collect();
        foreach ($billLines as $bl) {
            $merged->push(['product_id' => $bl->product_id, 'total' => (float) $bl->total, 'qty' => (float) $bl->qty, 'count' => (int) $bl->count]);
        }
        foreach ($expenseLines as $el) {
            $existing = $merged->firstWhere('product_id', $el->product_id);
            if ($existing) {
                $existing['total'] += (float) $el->total;
                $existing['qty'] += (float) $el->qty;
                $existing['count'] += (int) $el->count;
            } else {
                $merged->push(['product_id' => $el->product_id, 'total' => (float) $el->total, 'qty' => (float) $el->qty, 'count' => (int) $el->count]);
            }
        }

        $productIds = $merged->pluck('product_id')->filter();
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        $results = $merged->map(function ($l) use ($products) {
            return [
                'product_id' => $l['product_id'],
                'product_name' => $products[$l['product_id']]->name ?? 'N/A',
                'sku' => $products[$l['product_id']]->sku ?? '',
                'total' => $l['total'],
                'qty' => $l['qty'],
                'count' => $l['count'],
            ];
        })->sortByDesc('total')->values()->toArray();

        return ['items' => $results, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
