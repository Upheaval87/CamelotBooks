<?php
namespace App\Services\Reporting;
use App\Models\BillLine;
use Illuminate\Support\Facades\DB;

class PurchasesByItemService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $lines = BillLine::whereHas('bill', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->forCompany($companyId)->whereIn('status', ['posted', 'partially_paid', 'paid'])
                ->where('bill_date', '>=', $dateFrom)->where('bill_date', '<=', $dateTo);
        })->select('product_id', DB::raw('SUM(line_total) as total'), DB::raw('SUM(quantity) as qty'), DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')->get();

        $productIds = $lines->pluck('product_id')->filter();
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        $results = $lines->map(function ($l) use ($products) {
            return [
                'product_id' => $l->product_id,
                'product_name' => $products[$l->product_id]->name ?? 'N/A',
                'sku' => $products[$l->product_id]->sku ?? '',
                'total' => (float) $l->total,
                'qty' => (float) $l->qty,
                'count' => (int) $l->count,
            ];
        })->sortByDesc('total')->values()->toArray();

        return ['items' => $results, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
