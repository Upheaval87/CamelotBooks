<?php

namespace App\Services\Reporting;

use App\Models\Bill;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class PurchasesByVendorService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $billSums = Bill::forCompany($companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->where('bill_date', '>=', $dateFrom)
            ->where('bill_date', '<=', $dateTo)
            ->select('vendor_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('vendor_id')
            ->get();

        $expenseSums = Expense::forCompany($companyId)
            ->where('status', 'posted')
            ->where('expense_date', '>=', $dateFrom)
            ->where('expense_date', '<=', $dateTo)
            ->select('vendor_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('vendor_id')
            ->get();

        $merged = collect();
        foreach ($billSums as $b) {
            $merged->push(['vendor_id' => $b->vendor_id, 'total' => (float) $b->total, 'count' => (int) $b->count]);
        }
        foreach ($expenseSums as $e) {
            $existing = $merged->firstWhere('vendor_id', $e->vendor_id);
            if ($existing) {
                $existing['total'] += (float) $e->total;
                $existing['count'] += (int) $e->count;
            } else {
                $merged->push(['vendor_id' => $e->vendor_id, 'total' => (float) $e->total, 'count' => (int) $e->count]);
            }
        }

        $vendorIds = $merged->pluck('vendor_id')->filter();
        $vendors = \App\Models\Vendor::whereIn('id', $vendorIds)->get()->keyBy('id');

        $results = $merged->map(function ($b) use ($vendors) {
            return [
                'vendor_id' => $b['vendor_id'],
                'vendor_name' => $vendors[$b['vendor_id']]->name ?? 'N/A',
                'total' => $b['total'],
                'count' => $b['count'],
            ];
        })->sortByDesc('total')->values()->toArray();

        return ['vendors' => $results, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
