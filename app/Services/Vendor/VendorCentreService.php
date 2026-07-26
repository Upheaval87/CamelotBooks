<?php

namespace App\Services\Vendor;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Models\VendorPayment;
use Illuminate\Support\Collection;

class VendorCentreService
{
    public function getVendorSummary(int $companyId): Collection
    {
        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($vendors as $vendor) {
            $vendor->total_bills = Bill::where('vendor_id', $vendor->id)
                ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
                ->sum('amount');

            $vendor->total_paid = Bill::where('vendor_id', $vendor->id)
                ->sum('amount_paid');

            $vendor->open_balance = (float) $vendor->total_bills - (float) $vendor->total_paid;

            $vendor->open_pos = PurchaseOrder::where('vendor_id', $vendor->id)
                ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT])
                ->count();

            $vendor->credit_balance = VendorCredit::where('vendor_id', $vendor->id)
                ->where('status', '!=', VendorCredit::STATUS_VOID)
                ->sum('amount') - VendorCredit::where('vendor_id', $vendor->id)
                ->where('status', '!=', VendorCredit::STATUS_VOID)
                ->sum('amount_applied');

            $vendor->expense_total = Expense::where('vendor_id', $vendor->id)
                ->where('status', Expense::STATUS_POSTED)
                ->sum('amount');
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
        $totalBills = Bill::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->sum('amount');

        $totalPaid = Bill::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->sum('amount_paid');

        $totalExpenses = Expense::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->where('status', Expense::STATUS_POSTED)
            ->sum('amount');

        $totalCredits = VendorCredit::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->where('status', '!=', VendorCredit::STATUS_VOID)
            ->sum('amount');

        $totalCreditsApplied = VendorCredit::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->where('status', '!=', VendorCredit::STATUS_VOID)
            ->sum('amount_applied');

        $billCount = Bill::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->count();

        $poCount = PurchaseOrder::where('vendor_id', $vendor->id)
            ->where('company_id', $companyId)
            ->count();

        return [
            'total_bills' => round((float) $totalBills, 2),
            'total_paid' => round((float) $totalPaid, 2),
            'open_balance' => round((float) $totalBills - (float) $totalPaid, 2),
            'total_expenses' => round((float) $totalExpenses, 2),
            'credit_balance' => round((float) $totalCredits - (float) $totalCreditsApplied, 2),
            'bill_count' => $billCount,
            'po_count' => $poCount,
        ];
    }
}
