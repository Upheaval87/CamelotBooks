<?php

namespace App\Services\Reporting;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\CustomerPayment;
use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;

class CustomerStatementService
{
    public function generate(int $companyId, int $customerId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $customer = Customer::where('company_id', $companyId)->findOrFail($customerId);

        $transactions = collect();

        $invoices = Invoice::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['sent', 'partially_paid', 'paid'])
            ->when($dateFrom, fn ($q) => $q->where('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('invoice_date', '<=', $dateTo))
            ->get()
            ->map(fn ($inv) => [
                'date' => $inv->invoice_date,
                'type' => 'Invoice',
                'reference' => $inv->invoice_number,
                'debit' => (float) $inv->amount,
                'credit' => 0,
                'balance' => 0,
            ]);

        $payments = CustomerPayment::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->when($dateFrom, fn ($q) => $q->where('payment_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('payment_date', '<=', $dateTo))
            ->get()
            ->map(fn ($p) => [
                'date' => $p->payment_date,
                'type' => 'Payment',
                'reference' => $p->payment_number,
                'debit' => 0,
                'credit' => (float) $p->amount,
                'balance' => 0,
            ]);

        $creditNotes = CreditNote::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['posted', 'applied'])
            ->when($dateFrom, fn ($q) => $q->where('credit_note_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('credit_note_date', '<=', $dateTo))
            ->get()
            ->map(fn ($cn) => [
                'date' => $cn->credit_note_date,
                'type' => 'Credit Note',
                'reference' => $cn->credit_note_number,
                'debit' => 0,
                'credit' => (float) $cn->amount,
                'balance' => 0,
            ]);

        $transactions = $invoices->concat($payments)->concat($creditNotes)->sortBy('date')->values();

        $runningBalance = (float) $customer->opening_balance;
        foreach ($transactions as &$txn) {
            $runningBalance += $txn['debit'] - $txn['credit'];
            $txn['balance'] = round($runningBalance, 2);
        }

        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');

        return [
            'customer' => $customer,
            'transactions' => $transactions->toArray(),
            'opening_balance' => (float) $customer->opening_balance,
            'closing_balance' => round($runningBalance, 2),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
