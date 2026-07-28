<?php
namespace App\Services\Reporting;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

class PurchasesByVendorService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $bills = Bill::forCompany($companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->where('bill_date', '>=', $dateFrom)
            ->where('bill_date', '<=', $dateTo)
            ->select('vendor_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('vendor_id')
            ->get();

        $vendorIds = $bills->pluck('vendor_id')->filter();
        $vendors = \App\Models\Vendor::whereIn('id', $vendorIds)->get()->keyBy('id');

        $results = $bills->map(function ($b) use ($vendors) {
            return [
                'vendor_id' => $b->vendor_id,
                'vendor_name' => $vendors[$b->vendor_id]->name ?? 'N/A',
                'total' => (float) $b->total,
                'count' => (int) $b->count,
            ];
        })->sortByDesc('total')->values()->toArray();

        return ['vendors' => $results, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
