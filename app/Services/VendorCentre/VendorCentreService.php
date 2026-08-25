<?php

namespace App\Services\VendorCentre;

use App\Models\Bill;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Models\VendorPayment;
use App\Services\Reporting\AgingReportService;
use Carbon\Carbon;

class VendorCentreService
{
    private ?array $agingCache = null;

    public function __construct(
        private AgingReportService $agingService
    ) {}

    /* ── Shared metric: aging buckets ──────────────────────────── */

    public function getAgingBuckets(int $companyId): array
    {
        if (isset($this->agingCache[$companyId])) {
            return $this->agingCache[$companyId];
        }

        $this->agingCache[$companyId] = $this->agingService->apAging($companyId, null, now()->format('Y-m-d'));

        return $this->agingCache[$companyId];
    }

    public function clearCache(): void
    {
        $this->agingCache = null;
    }

    /* ── Shared metric: total payables ─────────────────────────── */

    public function getTotalPayables(int $companyId): float
    {
        return (float) $this->getAgingBuckets($companyId)['totals']['total'];
    }

    /* ── Shared metric: aging bar data ─────────────────────────── */

    public function getAgingBarData(int $companyId): array
    {
        $totals = $this->getAgingBuckets($companyId)['totals'];

        $buckets = [
            'current' => ['label' => 'Current', 'amount' => (float) $totals['current'], 'color' => 'var(--sec)'],
            'days_1_30' => ['label' => '1–30', 'amount' => (float) $totals['days_1_30'], 'color' => '#3aa7a0'],
            'days_31_60' => ['label' => '31–60', 'amount' => (float) $totals['days_31_60'], 'color' => 'var(--amber)'],
            'days_61_90' => ['label' => '61–90', 'amount' => (float) $totals['days_61_90'], 'color' => '#d97706'],
            'days_91_120' => ['label' => '91–120', 'amount' => (float) ($totals['days_90_plus'] ?? 0), 'color' => 'var(--red)'],
            'days_120_plus' => ['label' => '120+', 'amount' => 0, 'color' => 'var(--red)'],
        ];

        $maxAmounts = array_column($buckets, 'amount');
        $max = max($maxAmounts) ?: 0.01;
        foreach ($buckets as &$bucket) {
            $bucket['pct'] = round(($bucket['amount'] / $max) * 100, 1);
        }

        return $buckets;
    }

    /* ── Shared metric: due this week (7-day window) ───────────── */

    public function getDueThisWeek(int $companyId): array
    {
        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];
        $today = Carbon::today();
        $weekEnd = Carbon::today()->addDays(6);

        $bills = Bill::where('company_id', $companyId)
            ->whereIn('status', $openStatuses)
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $weekEnd)
            ->with('vendor')
            ->orderBy('due_date')
            ->get();

        $totalAmount = 0;
        $vendorPayments = [];

        foreach ($bills as $bill) {
            $totalAmount += (float) $bill->balance_due;
            $vendorPayments[] = [
                'vendor_name' => $bill->vendor->name ?? 'Unknown',
                'vendor_id' => $bill->vendor_id,
                'due_date' => $bill->due_date,
                'amount' => (float) $bill->balance_due,
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_number,
            ];
        }

        return [
            'count' => $bills->count(),
            'total_amount' => $totalAmount,
            'payments' => $vendorPayments,
        ];
    }

    /* ── Shared metric: overdue stats ──────────────────────────── */

    public function getOverdueStats(int $companyId): array
    {
        $aging = $this->getAgingBuckets($companyId);
        $overdueAmount = (float) $aging['totals']['days_1_30']
            + (float) $aging['totals']['days_31_60']
            + (float) $aging['totals']['days_61_90']
            + (float) $aging['totals']['days_90_plus'];

        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];
        $vendorCount = Bill::where('company_id', $companyId)
            ->whereIn('status', $openStatuses)
            ->whereDate('due_date', '<', now()->toDateString())
            ->distinct('vendor_id')
            ->count('vendor_id');

        return [
            'amount' => $overdueAmount,
            'vendor_count' => $vendorCount,
        ];
    }

    /* ── Shared metric: purchases YTD + comparison ─────────────── */

    public function getPurchasesYTD(int $companyId): array
    {
        $yearStart = Carbon::now()->startOfYear()->toDateString();
        $today = Carbon::today()->toDateString();
        $lastYearStart = Carbon::now()->subYear()->startOfYear()->toDateString();
        $lastYearToday = Carbon::now()->subYear()->toDateString();

        $ytdTotal = (float) Bill::where('company_id', $companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_PAID, Bill::STATUS_OVERDUE])
            ->whereDate('bill_date', '>=', $yearStart)
            ->whereDate('bill_date', '<=', $today)
            ->sum('amount');

        $lyTotal = (float) Bill::where('company_id', $companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_PAID, Bill::STATUS_OVERDUE])
            ->whereDate('bill_date', '>=', $lastYearStart)
            ->whereDate('bill_date', '<=', $lastYearToday)
            ->sum('amount');

        $pctChange = $lyTotal > 0 ? round((($ytdTotal - $lyTotal) / $lyTotal) * 100, 1) : 0;

        return [
            'ytd' => $ytdTotal,
            'last_year' => $lyTotal,
            'pct_change' => $pctChange,
        ];
    }

    /* ── Shared metric: upcoming payments (≤30 days) ───────────── */

    public function getUpcomingPayments(int $companyId): array
    {
        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];
        $today = Carbon::today();
        $window = Carbon::today()->addDays(30);

        $bills = Bill::where('company_id', $companyId)
            ->whereIn('status', $openStatuses)
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $window)
            ->with('vendor')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $result = [];
        foreach ($bills as $bill) {
            $dueDate = Carbon::parse($bill->due_date);
            $daysUntil = $today->diffInDays($dueDate, false);

            $result[] = [
                'vendor_name' => $bill->vendor->name ?? 'Unknown',
                'vendor_id' => $bill->vendor_id,
                'due_date' => $bill->due_date,
                'due_label' => $this->dueLabel($dueDate),
                'amount' => (float) $bill->balance_due,
                'dot_color' => self::severityDot($bill->due_date),
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_number,
            ];
        }

        return $result;
    }

    /* ── Shared metric: top vendors ────────────────────────────── */

    public function getTopVendors(int $companyId, string $sortBy = 'spend', int $limit = 5): array
    {
        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];
        $billedStatuses = array_merge($openStatuses, [Bill::STATUS_PAID]);

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->withSum(['bills' => fn ($q) => $q->whereIn('status', $billedStatuses)], 'amount')
            ->withCount(['bills' => fn ($q) => $q->whereIn('status', $billedStatuses)])
            ->get();

        $result = $vendors->map(function ($v) use ($openStatuses) {
            $totalBilled = (float) ($v->bills_sum_amount ?? 0);
            $outstanding = (float) $v->bills()
                ->whereIn('status', $openStatuses)
                ->sum('amount');
            $txCount = $v->bills_count ?? 0;
            $lastBill = $v->bills()->orderBy('bill_date', 'desc')->first();

            return [
                'vendor_id' => $v->id,
                'vendor_name' => $v->name,
                'purchases' => $totalBilled,
                'outstanding' => $outstanding,
                'transactions' => $txCount,
                'last_purchase' => $lastBill?->bill_date,
            ];
        });

        return match ($sortBy) {
            'out' => $result->sortByDesc('outstanding')->values()->take($limit)->all(),
            'count' => $result->sortByDesc('transactions')->values()->take($limit)->all(),
            default => $result->sortByDesc('purchases')->values()->take($limit)->all(),
        };
    }

    /* ── Shared metric: pending transactions ───────────────────── */

    public function getPendingTransactions(int $companyId): array
    {
        $poDraft = PurchaseOrder::where('company_id', $companyId)
            ->whereIn('status', [PurchaseOrder::STATUS_DRAFT])
            ->count();

        $poSent = PurchaseOrder::where('company_id', $companyId)
            ->where('status', PurchaseOrder::STATUS_PARTIALLY_RECEIVED)
            ->count();

        $billPending = Bill::where('company_id', $companyId)
            ->where('status', Bill::STATUS_PENDING_APPROVAL)
            ->count();

        $billUnpaid = Bill::where('company_id', $companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID])
            ->count();

        $paymentPending = VendorPayment::where('company_id', $companyId)
            ->where('status', VendorPayment::STATUS_PENDING_APPROVAL)
            ->count();

        $creditPending = VendorCredit::where('company_id', $companyId)
            ->where('status', 'draft')
            ->count();

        return [
            ['stage' => 'Purchase Orders', 'status' => 'Awaiting Approval', 'count' => $poDraft],
            ['stage' => 'Purchase Orders', 'status' => 'Partially Received', 'count' => $poSent],
            ['stage' => 'Goods Received', 'status' => 'Awaiting Invoice / Verification', 'count' => $creditPending],
            ['stage' => 'Purchase Invoices', 'status' => 'Awaiting Approval', 'count' => $billPending],
            ['stage' => 'Purchase Invoices', 'status' => 'Unpaid / Partially Paid', 'count' => $billUnpaid],
            ['stage' => 'Payments', 'status' => 'Pending Authorization', 'count' => $paymentPending],
        ];
    }

    /* ── Shared metric: alert counts ───────────────────────────── */

    public function getAlertCounts(int $companyId): array
    {
        $overdue = $this->getOverdueStats($companyId);

        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];
        $dueWithin7 = Bill::where('company_id', $companyId)
            ->whereIn('status', $openStatuses)
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays(6)->toDateString())
            ->count();

        $awaitingAuth = VendorPayment::where('company_id', $companyId)
            ->where('status', VendorPayment::STATUS_PENDING_APPROVAL)
            ->count();

        return [
            'overdue_vendors' => $overdue['vendor_count'],
            'due_within_7_days' => $dueWithin7,
            'awaiting_authorization' => $awaitingAuth,
        ];
    }

    /* ── Shared metric: vendor balances (top N by closing) ─────── */

    public function getVendorBalances(int $companyId, int $limit = 5): array
    {
        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $result = [];

        foreach ($vendors as $vendor) {
            $openingBalance = (float) $vendor->opening_balance;

            $purchases = (float) Bill::where('vendor_id', $vendor->id)
                ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_PAID, Bill::STATUS_OVERDUE])
                ->sum('amount');

            $payments = (float) VendorPayment::where('vendor_id', $vendor->id)
                ->where('status', 'posted')
                ->sum('amount');

            $returns = (float) VendorCredit::where('vendor_id', $vendor->id)
                ->where('status', 'posted')
                ->sum('amount');

            $closing = $openingBalance + $purchases - $payments - $returns;

            $result[] = [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'opening' => $openingBalance,
                'purchases' => $purchases,
                'payments' => $payments,
                'returns' => $returns,
                'closing' => $closing,
            ];
        }

        usort($result, fn ($a, $b) => $b['closing'] <=> $a['closing']);

        return array_slice($result, 0, $limit);
    }

    /* ── Shared metric: vendor count ───────────────────────────── */

    public function getVendorCount(int $companyId): array
    {
        $total = Vendor::where('company_id', $companyId)->count();
        $active = Vendor::where('company_id', $companyId)->where('is_active', true)->count();

        return [
            'total' => $total,
            'active' => $active,
        ];
    }

    /* ── Severity dot color (§1.2) ─────────────────────────────── */

    public static function severityDot(string $dueDate): string
    {
        $due = Carbon::parse($dueDate);
        $today = Carbon::today();

        if ($due->isSameDay($today)) {
            return 'var(--red)';
        }
        if ($due->isSameDay($today->copy()->addDay())) {
            return '#d97706';
        }
        if ($due->lte($today->copy()->addDays(6))) {
            return 'var(--amber)';
        }
        return 'var(--green)';
    }

    /* ── Due label helper ──────────────────────────────────────── */

    private function dueLabel(Carbon $dueDate): string
    {
        $today = Carbon::today();

        if ($dueDate->isSameDay($today)) {
            return 'Today';
        }
        if ($dueDate->isSameDay($today->copy()->addDay())) {
            return 'Tomorrow';
        }
        return $dueDate->format('d M');
    }

    /* ── Format compact currency ───────────────────────────────── */

    public static function compactAmount(float $amount, string $currencySymbol = 'K'): string
    {
        $abs = abs($amount);
        $sign = $amount < 0 ? '-' : '';

        if ($abs >= 1_000_000) {
            return $sign . $currencySymbol . number_format($abs / 1_000_000, 1) . 'M';
        }
        if ($abs >= 1_000) {
            return $sign . $currencySymbol . number_format($abs / 1_000, 0);
        }
        return $sign . $currencySymbol . number_format($abs, 2);
    }
}
