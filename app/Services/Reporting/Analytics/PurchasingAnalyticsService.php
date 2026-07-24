<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Bill;
use App\Models\BillLine;
use App\Models\Vendor;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchasingAnalyticsService
{
    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $monthlyBills = Bill::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('bill_date', '>=', $dateFrom)
            ->where('bill_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(['bill_date', 'amount'])
            ->get()
            ->groupBy(fn ($bill) => Carbon::parse($bill->bill_date)->format('Y-m'))
            ->map(fn ($group) => [
                'month' => $group->first()->bill_date ? Carbon::parse($group->first()->bill_date)->format('Y-m') : '',
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ])
            ->values()
            ->sortBy('month')
            ->values();

        $topVendors = Bill::where('bills.company_id', $companyId)
            ->whereIn('bills.status', ['posted', 'paid', 'partially_paid'])
            ->where('bills.bill_date', '>=', $dateFrom)
            ->where('bills.bill_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('bills.branch_id', $branchId))
            ->join('vendors', 'bills.vendor_id', '=', 'vendors.id')
            ->selectRaw('vendors.id as vendor_id, vendors.name as vendor_name, SUM(bills.amount) as total_spend, COUNT(bills.id) as bill_count')
            ->groupBy('vendors.id', 'vendors.name')
            ->orderByDesc('total_spend')
            ->limit(10)
            ->get();

        $ppvAccount = Account::where('company_id', $companyId)->where('code', '6800')->first();
        $ppvTrend = collect();
        $ppvTotal = 0;

        if ($ppvAccount) {
            $ppvRows = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entry_lines.account_id', $ppvAccount->id)
                ->where('journal_entries.company_id', $companyId)
                ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                ->where('journal_entries.date', '>=', $dateFrom)
                ->where('journal_entries.date', '<=', $dateTo)
                ->select(['journal_entries.date', 'journal_entry_lines.debit', 'journal_entry_lines.credit'])
                ->get();
            $ppvTrend = $ppvRows
                ->groupBy(fn ($row) => Carbon::parse($row->date)->format('Y-m'))
                ->map(fn ($group, $month) => [
                    'month' => $month,
                    'net_amount' => $group->sum('debit') - $group->sum('credit'),
                ])
                ->values()
                ->sortBy('month')
                ->values();
            $ppvTotal = (float) $ppvTrend->sum('net_amount');
        }

        $leadTimes = BillLine::query()
            ->join('bills', 'bill_lines.bill_id', '=', 'bills.id')
            ->join('purchase_order_lines', 'bill_lines.purchase_order_line_id', '=', 'purchase_order_lines.id')
            ->join('purchase_orders', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->where('bills.company_id', $companyId)
            ->where('bills.bill_date', '>=', $dateFrom)
            ->where('bills.bill_date', '<=', $dateTo)
            ->whereNotNull('bill_lines.purchase_order_line_id')
            ->when($branchId, fn ($q) => $q->where('bills.branch_id', $branchId))
            ->selectRaw('bills.bill_date as bill_date, purchase_orders.date as order_date')
            ->get();

        $leadDays = $leadTimes->map(fn ($row) => Carbon::parse($row->bill_date)->diffInDays(Carbon::parse($row->order_date)));

        return [
            'monthly_summary' => $monthlyBills->toArray(),
            'top_vendors' => $topVendors->toArray(),
            'ppv_trend' => $ppvTrend->toArray(),
            'ppv_total' => $ppvTotal,
            'lead_times' => [
                'avg_days' => $leadDays->isNotEmpty() ? $leadDays->avg() : null,
                'min_days' => $leadDays->isNotEmpty() ? $leadDays->min() : null,
                'max_days' => $leadDays->isNotEmpty() ? $leadDays->max() : null,
            ],
            'labels' => $monthlyBills->pluck('month')->toArray(),
            'spend_data' => $monthlyBills->pluck('total')->map(fn ($v) => (float) $v)->toArray(),
            'bill_count_data' => $monthlyBills->pluck('count')->toArray(),
        ];
    }
}
