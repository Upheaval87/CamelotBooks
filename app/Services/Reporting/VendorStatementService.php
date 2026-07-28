<?php

namespace App\Services\Reporting;

use App\Models\Vendor;
use App\Models\Bill;
use App\Models\VendorPayment;
use App\Models\VendorCredit;

class VendorStatementService
{
    public function generate(int $companyId, int $vendorId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $vendor = Vendor::where('company_id', $companyId)->findOrFail($vendorId);

        $bills = Bill::where('company_id', $companyId)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->when($dateFrom, fn ($q) => $q->where('bill_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('bill_date', '<=', $dateTo))
            ->get()
            ->map(fn ($b) => [
                'date' => $b->bill_date,
                'type' => 'Bill',
                'reference' => $b->bill_number,
                'debit' => 0,
                'credit' => (float) $b->amount,
                'balance' => 0,
            ]);

        $payments = VendorPayment::where('company_id', $companyId)
            ->where('vendor_id', $vendorId)
            ->when($dateFrom, fn ($q) => $q->where('payment_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('payment_date', '<=', $dateTo))
            ->get()
            ->map(fn ($p) => [
                'date' => $p->payment_date,
                'type' => 'Payment',
                'reference' => $p->payment_number,
                'debit' => (float) $p->amount,
                'credit' => 0,
                'balance' => 0,
            ]);

        $credits = VendorCredit::where('company_id', $companyId)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['posted', 'applied'])
            ->when($dateFrom, fn ($q) => $q->where('credit_note_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('credit_note_date', '<=', $dateTo))
            ->get()
            ->map(fn ($vc) => [
                'date' => $vc->credit_note_date,
                'type' => 'Vendor Credit',
                'reference' => $vc->credit_note_number,
                'debit' => (float) $vc->amount,
                'credit' => 0,
                'balance' => 0,
            ]);

        $transactions = $bills->concat($payments)->concat($credits)->sortBy('date')->values();

        $runningBalance = (float) $vendor->opening_balance;
        foreach ($transactions as &$txn) {
            $runningBalance += $txn['credit'] - $txn['debit'];
            $txn['balance'] = round($runningBalance, 2);
        }

        return [
            'vendor' => $vendor,
            'transactions' => $transactions->toArray(),
            'opening_balance' => (float) $vendor->opening_balance,
            'closing_balance' => round($runningBalance, 2),
            'total_bills' => $bills->sum(fn ($b) => (float) $b->amount),
            'total_payments' => $payments->sum(fn ($p) => (float) $p->amount),
            'total_credits' => $credits->sum(fn ($vc) => (float) $vc->amount),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
