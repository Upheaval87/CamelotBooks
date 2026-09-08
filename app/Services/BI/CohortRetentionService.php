<?php

namespace App\Services\BI;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CohortRetentionService
{
    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null, int $retentionWindowDays = 90): array
    {
        // First invoice month per customer + each subsequent month with activity.
        $monthExpr = DB::getDriverName() === 'sqlite'
            ? 'strftime("%Y-%m", invoice_date)'
            : 'DATE_FORMAT(invoice_date, "%Y-%m")';

        $rows = Invoice::selectRaw("customer_id, {$monthExpr} as ym")
            ->where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            // Only customers with an invoice in the CURRENT window are part of the report;
            // but cohorts start from their FIRST-ever invoice.
            ->get()
            ->map(fn ($r) => [
                'customer_id' => (int) $r->customer_id,
                'ym' => substr((string) $r->ym, 0, 7),
            ]);

        $customersInWindow = $rows->pluck('customer_id')->unique();

        // First invoice month per customer (all-time).
        $firstInvoice = DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->whereIn('customer_id', $customersInWindow)
            ->selectRaw("customer_id, MIN({$monthExpr}) as first_ym")
            ->groupBy('customer_id')
            ->pluck('first_ym', 'customer_id');

        $windowEnd = Carbon::parse($dateTo);
        $cohorts = [];
        foreach ($firstInvoice as $customerId => $firstYm) {
            $cohorts[$firstYm][$customerId] ??= 0;
        }

        foreach ($rows as $r) {
            $firstYm = $firstInvoice[$r['customer_id']] ?? null;
            if (!$firstYm) {
                continue;
            }
            if (!isset($cohorts[$firstYm][$r['customer_id']])) {
                $cohorts[$firstYm][$r['customer_id']] = 0;
            }
            // Bitmap of months active.
            $monthIndex = (int) (Carbon::parse($firstYm . '-01')->startOfMonth()->diffInMonths(Carbon::parse($r['ym'] . '-01')->startOfMonth()));
            $cohorts[$firstYm][$r['customer_id']] |= (1 << $monthIndex);
        }

        $grid = [];
        $churnData = [];
        foreach ($cohorts as $firstYm => $members) {
            $count = 0;
            $retained = [];
            $churn = 0;
            $lastCutoff = Carbon::parse($dateTo)->subDays($retentionWindowDays);
            foreach ($members as $customerId => $bitmap) {
                $count++;
                $firstDate = Carbon::parse($firstYm . '-01')->startOfMonth();
                for ($offset = 0; $offset <= 5; $offset++) {
                    $hasActivity = ($bitmap >> $offset) & 1;
                    $retained[$offset] = ($retained[$offset] ?? 0) + ($hasActivity ? 1 : 0);
                }
                // Churn: no invoice in the trailing window before dateTo.
                $lastActive = $this->lastActiveMonth($bitmap, $firstDate);
                if ($lastActive && $lastActive->lt($lastCutoff)) {
                    $churn++;
                }
            }

            $row = ['cohort' => $firstYm, 'customers' => $count];
            for ($offset = 0; $offset <= 5; $offset++) {
                $row["m{$offset}"] = $count > 0 ? round(($retained[$offset] ?? 0) / $count * 100, 1) : 0;
            }
            $grid[] = $row;
            $churnData[$firstYm] = ['customers' => $count, 'churned' => $churn];
        }

        usort($grid, fn ($a, $b) => strcmp($a['cohort'], $b['cohort']));
        ksort($churnData);

        $totalCustomers = count($customersInWindow);
        $totalChurned = array_sum(array_column($churnData, 'churned'));

        return [
            'grid' => $grid,
            'churn' => $churnData,
            'total_customers' => $totalCustomers,
            'churned_customers' => $totalChurned,
            'churn_rate_pct' => $totalCustomers > 0 ? round($totalChurned / $totalCustomers * 100, 1) : 0,
            'retention_window_days' => $retentionWindowDays,
        ];
    }

    protected function lastActiveMonth(int $bitmap, Carbon $firstDate): ?Carbon
    {
        if ($bitmap === 0) {
            return null;
        }
        for ($offset = 31; $offset >= 0; $offset--) {
            if (($bitmap >> $offset) & 1) {
                return $firstDate->copy()->addMonths($offset);
            }
        }
        return null;
    }
}