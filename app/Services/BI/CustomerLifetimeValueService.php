<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;

class CustomerLifetimeValueService
{
    public function calculate(int $companyId, ?int $branchId = null): array
    {
        $customers = DB::table('fact_sales AS fs')
            ->leftJoin('dim_customer AS dc', 'dc.customer_key', '=', 'fs.customer_key')
            ->where('fs.company_key', $companyId)
            ->when($branchId, fn ($q) => $q->where('fs.branch_key', $branchId))
            ->select(
                'fs.customer_key',
                'dc.customer_name',
                'dc.email',
                DB::raw("SUM(CASE WHEN fs.is_credit_note = 0 THEN fs.line_total ELSE 0 END) AS total_revenue"),
                DB::raw("SUM(CASE WHEN fs.is_credit_note = 1 THEN ABS(fs.line_total) ELSE 0 END) AS total_credits"),
                DB::raw("SUM(CASE WHEN fs.is_credit_note = 0 THEN fs.line_total ELSE -ABS(fs.line_total) END) AS net_revenue"),
                DB::raw("COUNT(DISTINCT fs.source_id) AS invoice_count"),
                DB::raw("MIN(dd.full_date) AS first_invoice_date"),
                DB::raw("MAX(dd.full_date) AS last_invoice_date")
            )
            ->leftJoin('dim_date AS dd', 'dd.date_key', '=', 'fs.date_key')
            ->groupBy('fs.customer_key', 'dc.customer_name', 'dc.email')
            ->orderByDesc('net_revenue')
            ->get()
            ->map(function ($row) {
                $first = $row->first_invoice_date ? \Carbon\Carbon::parse($row->first_invoice_date) : now();
                $last = $row->last_invoice_date ? \Carbon\Carbon::parse($row->last_invoice_date) : now();
                $months = max(1, $first->diffInMonths($last) + 1);
                $row->avg_monthly_revenue = $row->net_revenue / $months;
                $row->months_active = $months;
                return $row;
            });

        return [
            'customers'    => $customers->toArray(),
            'total_customers' => $customers->count(),
            'total_revenue'   => $customers->sum('net_revenue'),
        ];
    }
}
