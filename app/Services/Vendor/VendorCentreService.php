<?php

namespace App\Services\Vendor;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Models\VendorPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VendorCentreService
{
    public function getVendorSummary(int $companyId): Collection
    {
        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $vendorIds = $vendors->pluck('id');

        if ($vendorIds->isEmpty()) {
            return $vendors;
        }

        // Batch all aggregates with GROUP BY — 5 total queries regardless of vendor count
        $billTotals = Bill::whereIn('vendor_id', $vendorIds)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->select('vendor_id',
                DB::raw('COALESCE(SUM(amount), 0) as total_bills'),
                DB::raw('COALESCE(SUM(amount_paid), 0) as total_paid'))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $openPos = PurchaseOrder::whereIn('vendor_id', $vendorIds)
            ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT])
            ->select('vendor_id', DB::raw('COUNT(*) as open_pos'))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $creditTotals = VendorCredit::whereIn('vendor_id', $vendorIds)
            ->where('status', '!=', VendorCredit::STATUS_VOID)
            ->select('vendor_id',
                DB::raw('COALESCE(SUM(amount), 0) as credit_amount'),
                DB::raw('COALESCE(SUM(amount_applied), 0) as credit_applied'))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $expenseTotals = Expense::whereIn('vendor_id', $vendorIds)
            ->where('status', Expense::STATUS_POSTED)
            ->select('vendor_id', DB::raw('COALESCE(SUM(amount), 0) as expense_total'))
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        foreach ($vendors as $vendor) {
            $bills = $billTotals->get($vendor->id);
            $vendor->total_bills = $bills ? (float) $bills->total_bills : 0;
            $vendor->total_paid = $bills ? (float) $bills->total_paid : 0;
            $vendor->open_balance = $vendor->total_bills - $vendor->total_paid;

            $pos = $openPos->get($vendor->id);
            $vendor->open_pos = $pos ? (int) $pos->open_pos : 0;

            $credits = $creditTotals->get($vendor->id);
            $vendor->credit_balance = $credits ? (float) $credits->credit_amount - (float) $credits->credit_applied : 0;

            $expenses = $expenseTotals->get($vendor->id);
            $vendor->expense_total = $expenses ? (float) $expenses->expense_total : 0;
        }

        return $vendors;
    }

    public function getVendorTimeline(Vendor $vendor, int $companyId): Collection
    {
        $bills = Bill::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->get()
            ->map(fn ($bill) => [
                'type' => 'bill',
                'label' => 'Bill',
                'reference' => $bill->bill_number,
                'date' => $bill->bill_date,
                'amount' => $bill->amount,
                'status' => $bill->status,
                'url' => route('accounting.bills.show', $bill),
            ]);

        $payments = VendorPayment::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->get()
            ->map(fn ($payment) => [
                'type' => 'payment',
                'label' => 'Payment',
                'reference' => $payment->payment_number,
                'date' => $payment->payment_date,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'url' => route('accounting.vendor-payments.show', $payment),
            ]);

        $credits = VendorCredit::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->get()
            ->map(fn ($credit) => [
                'type' => 'credit',
                'label' => 'Vendor Credit',
                'reference' => $credit->credit_note_number,
                'date' => $credit->credit_note_date,
                'amount' => $credit->amount,
                'status' => $credit->status,
                'url' => route('accounting.vendor-credits.show', $credit),
            ]);

        $pos = PurchaseOrder::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->get()
            ->map(fn ($po) => [
                'type' => 'po',
                'label' => 'Purchase Order',
                'reference' => $po->po_number,
                'date' => $po->order_date,
                'amount' => $po->total_amount,
                'status' => $po->status,
                'url' => route('accounting.purchase-orders.show', $po),
            ]);

        $expenses = Expense::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->get()
            ->map(fn ($expense) => [
                'type' => 'expense',
                'label' => 'Expense',
                'reference' => $expense->expense_number,
                'date' => $expense->expense_date,
                'amount' => $expense->amount,
                'status' => $expense->status,
                'url' => route('accounting.expenses.show', $expense),
            ]);

        return $bills->concat($payments)->concat($credits)->concat($pos)->concat($expenses)
            ->sortByDesc('date')
            ->values();
    }

    public function getVendorStats(Vendor $vendor, int $companyId): array
    {
        // Combined bill aggregates: 1 query instead of 3
        $billAgg = Bill::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_bills')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as total_paid')
            ->selectRaw('COUNT(*) as bill_count')
            ->first();

        $totalExpenses = (float) Expense::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->where('status', Expense::STATUS_POSTED)
            ->sum('amount');

        // Combined credit aggregates: 1 query instead of 2
        $creditAgg = VendorCredit::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->where('status', '!=', VendorCredit::STATUS_VOID)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_credits')
            ->selectRaw('COALESCE(SUM(amount_applied), 0) as total_applied')
            ->first();

        $poCount = PurchaseOrder::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->count();

        $totalBills = (float) ($billAgg->total_bills ?? 0);
        $totalPaid = (float) ($billAgg->total_paid ?? 0);
        $billCount = (int) ($billAgg->bill_count ?? 0);

        return [
            'total_bills' => round($totalBills, 2),
            'total_paid' => round($totalPaid, 2),
            'open_balance' => round($totalBills - $totalPaid, 2),
            'total_expenses' => round($totalExpenses, 2),
            'credit_balance' => round((float) ($creditAgg->total_credits ?? 0) - (float) ($creditAgg->total_applied ?? 0), 2),
            'bill_count' => $billCount,
            'po_count' => $poCount,
        ];
    }
}
