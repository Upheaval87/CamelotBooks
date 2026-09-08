<?php

namespace App\Services\BI;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PosSale;
use Illuminate\Support\Facades\DB;

class CustomerLifetimeValueService
{
    public function calculate(int $companyId, ?int $branchId = null): array
    {
        // Invoices (posted, paid, partially paid) per customer.
        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, COUNT(*) as cnt, SUM(amount) as amount, MIN(invoice_date) as first_date, MAX(invoice_date) as last_date')
            ->groupBy('customer_id')
            ->get();

        // POS sales (posted) per customer.
        $pos = PosSale::where('company_id', $companyId)
            ->whereIn('status', [PosSale::STATUS_POSTED])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, COUNT(*) as cnt, SUM(total) as amount, MIN(created_at) as first_date, MAX(created_at) as last_date')
            ->groupBy('customer_id')
            ->get();

        // Credit notes (posted) per customer.
        $credits = CreditNote::where('company_id', $companyId)
            ->whereIn('status', [CreditNote::STATUS_POSTED])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, COUNT(*) as cnt, SUM(amount) as amount')
            ->groupBy('customer_id')
            ->get();

        $rows = [];
        $seen = [];

        $merge = function ($source, string $type) use (&$rows, &$seen) {
            foreach ($source as $r) {
                $cid = (int) $r->customer_id;
                if ($cid <= 0) {
                    continue;
                }
                if (!isset($seen[$cid])) {
                    $seen[$cid] = [
                        'customer_id' => $cid,
                        'invoice_count' => 0,
                        'pos_count' => 0,
                        'credit_count' => 0,
                        'total_revenue' => 0.0,
                        'first_date' => null,
                        'last_date' => null,
                    ];
                }
                $date = $r->first_date ?? $r->last_date ?? null;
                if ($type === 'credit') {
                    $seen[$cid]['credit_count'] += (int) $r->cnt;
                    $seen[$cid]['total_revenue'] -= (float) $r->amount;
                } else {
                    $seen[$cid][$type === 'pos' ? 'pos_count' : 'invoice_count'] += (int) $r->cnt;
                    $seen[$cid]['total_revenue'] += (float) $r->amount;
                }
                if ($date) {
                    $d = \Carbon\Carbon::parse($date);
                    $seen[$cid]['first_date'] = $seen[$cid]['first_date'] === null || $d->lt($seen[$cid]['first_date'])
                        ? $d : $seen[$cid]['first_date'];
                    $seen[$cid]['last_date'] = $seen[$cid]['last_date'] === null || $d->gt($seen[$cid]['last_date'])
                        ? $d : $seen[$cid]['last_date'];
                }
            }
        };

        $merge($invoices, 'invoice');
        $merge($pos, 'pos');
        $merge($credits, 'credit');

        $customers = Customer::where('company_id', $companyId)->get(['id', 'name', 'display_name', 'email', 'phone'])->keyBy('id');

        $now = now();
        foreach ($seen as $cid => $s) {
            $orderCount = $s['invoice_count'] + $s['pos_count'];
            $monthsActive = ($s['first_date'] && $s['last_date'])
                ? max(1, $s['first_date']->diffInMonths($s['last_date']) + 1)
                : 1;
            $lapsed = $s['last_date'] && $s['last_date']->lt($now->copy()->subDays(180));

            $rows[] = [
                'customer_id' => $cid,
                'customer_name' => $customers->get($cid)->name ?? ($customers->get($cid)->display_name ?? "Customer #{$cid}"),
                'email' => $customers->get($cid)->email ?? null,
                'phone' => $customers->get($cid)->phone ?? null,
                'invoice_count' => $s['invoice_count'],
                'pos_count' => $s['pos_count'],
                'credit_count' => $s['credit_count'],
                'order_count' => $orderCount,
                'total_revenue' => round($s['total_revenue'], 2),
                'first_purchase' => $s['first_date']?->toDateString(),
                'last_purchase' => $s['last_date']?->toDateString(),
                'months_active' => $monthsActive,
                'avg_order_value' => $orderCount > 0 ? round($s['total_revenue'] / $orderCount, 2) : 0,
                'purchases_per_month' => round($orderCount / $monthsActive, 2),
                'avg_monthly_revenue' => round($s['total_revenue'] / $monthsActive, 2),
                'est_ltv' => round($s['total_revenue'], 2),
                'segment' => $this->segment($s['total_revenue']),
                'lapsed' => $lapsed,
            ];
        }

        usort($rows, fn ($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        $totalRevenue = array_sum(array_column($rows, 'total_revenue'));
        $distinct = count($rows);

        return [
            'customers' => $rows,
            'total_customers' => $distinct,
            'total_revenue' => round($totalRevenue, 2),
            'avg_ltv' => $distinct > 0 ? round($totalRevenue / $distinct, 2) : 0,
            'active_customers' => count(array_filter($rows, fn ($r) => !$r['lapsed'])),
            'lapsed_customers' => count(array_filter($rows, fn ($r) => $r['lapsed'])),
        ];
    }

    private function segment(float $revenue): string
    {
        if ($revenue >= 10000) {
            return 'high';
        }
        if ($revenue >= 1000) {
            return 'medium';
        }
        return 'low';
    }
}