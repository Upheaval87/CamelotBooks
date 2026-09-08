<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PosSale;
use App\Models\SalesReceipt;
use Illuminate\Support\Facades\DB;

class CustomerAnalyticsService
{
    public function calculate(int $companyId, array $period, ?int $branchId = null): array
    {
        $active = $this->activeCustomerIds($companyId, $period['from'], $period['to'], $branchId);
        $prevActive = $this->activeCustomerIds($companyId, $period['prev_from'], $period['prev_to'], $branchId);

        $revenue = $this->revenueById($companyId, $period['from'], $period['to'], $branchId);

        $activeCount = $active->count();
        $churn = $prevActive->diff($active)->count();
        $newCount = Customer::where('company_id', $companyId)
            ->whereBetween('created_at', [$period['from'] . ' 00:00:00', $period['to'] . ' 23:59:59'])
            ->count();

        $totalRevenue = $revenue->sum('total');
        $top10 = $revenue->sortByDesc('total')->take(10);
        $top10Revenue = $top10->sum('total');
        $arpc = $activeCount > 0 ? $totalRevenue / $activeCount : 0;

        $topCustomers = $top10->values()->map(function ($row, $idx) use ($companyId) {
            $outstanding = Invoice::where('company_id', $companyId)
                ->where('customer_id', $row['customer_id'])
                ->whereIn('status', ['posted', 'partially_paid'])
                ->selectRaw('SUM(amount - amount_paid) as bal')
                ->first();

            return [
                'rank' => $idx + 1,
                'customer_id' => $row['customer_id'],
                'name' => $row['name'],
                'orders' => $row['count'],
                'revenue' => $row['total'],
                'share' => 0,
                'outstanding' => (float) ($outstanding->bal ?? 0),
            ];
        });

        return [
            'kpis' => [
                'active' => ['value' => $activeCount],
                'new' => ['value' => $newCount],
                'churn' => ['value' => $churn],
                'arpc' => ['value' => $arpc],
                'top10_share' => ['value' => $totalRevenue > 0 ? ($top10Revenue / $totalRevenue) * 100 : 0],
            ],
            'top_customers' => $topCustomers->all(),
            'total_revenue' => $totalRevenue,
        ];
    }

    private function activeCustomerIds(int $companyId, string $from, string $to, ?int $branchId)
    {
        $inv = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('customer_id');

        $pos = PosSale::where('company_id', $companyId)
            ->where('status', 'posted')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to . ' 23:59:59')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('customer_id');

        $rec = SalesReceipt::where('company_id', $companyId)
            ->where('status', 'posted')
            ->where('receipt_date', '>=', $from)
            ->where('receipt_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('customer_id');

        return $inv->concat($pos)->concat($rec)->filter()->unique()->values();
    }

    private function revenueById(int $companyId, string $from, string $to, ?int $branchId)
    {
        $rows = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('customer_id')
            ->get()
            ->map(function ($row) use ($companyId) {
                return [
                    'company_id' => $companyId,
                    'customer_id' => $row->customer_id,
                    'total' => (float) $row->total,
                    'count' => $row->count,
                ];
            });

        $pos = PosSale::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereNotNull('customer_id')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to . ' 23:59:59')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, SUM(total) as total, COUNT(*) as count')
            ->groupBy('customer_id')
            ->get()
            ->map(function ($row) use ($companyId) {
                return [
                    'company_id' => $companyId,
                    'customer_id' => $row->customer_id,
                    'total' => (float) $row->total,
                    'count' => $row->count,
                ];
            });

        $rec = SalesReceipt::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereNotNull('customer_id')
            ->where('receipt_date', '>=', $from)
            ->where('receipt_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, SUM(total) as total, COUNT(*) as count')
            ->groupBy('customer_id')
            ->get()
            ->map(function ($row) use ($companyId) {
                return [
                    'company_id' => $companyId,
                    'customer_id' => $row->customer_id,
                    'total' => (float) $row->total,
                    'count' => $row->count,
                ];
            });

        $merged = collect();
        foreach ([$rows, $pos, $rec] as $collection) {
            foreach ($collection as $item) {
                $key = $item['customer_id'];
                if ($merged->has($key)) {
                    $merged[$key]['total'] += $item['total'];
                    $merged[$key]['count'] += $item['count'];
                } else {
                    $merged[$key] = $item;
                }
            }
        }

        return $merged->values()->map(function ($item) {
            $customer = Customer::find($item['customer_id']);
            $item['name'] = $customer?->name ?? 'Unknown';
            return $item;
        });
    }
}