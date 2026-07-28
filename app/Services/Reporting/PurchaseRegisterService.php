<?php

namespace App\Services\Reporting;

use App\Models\Bill;
use Illuminate\Support\Facades\DB;

class PurchaseRegisterService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo): array
    {
        $bills = Bill::forCompany($companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])
            ->where('bill_date', '>=', $dateFrom)
            ->where('bill_date', '<=', $dateTo)
            ->with('vendor')
            ->orderBy('bill_date')
            ->get();

        $results = [];
        $totalAmount = 0;
        $totalTax = 0;

        foreach ($bills as $bill) {
            $results[] = [
                'bill_number' => $bill->bill_number,
                'date' => $bill->bill_date,
                'vendor_name' => $bill->vendor->name ?? 'N/A',
                'amount' => (float) $bill->amount,
                'tax_total' => (float) $bill->tax_total,
                'total' => (float) $bill->amount,
                'status' => $bill->status,
            ];
            $totalAmount += (float) $bill->amount;
            $totalTax += (float) $bill->tax_total;
        }

        return [
            'bills' => $results,
            'total_amount' => $totalAmount,
            'total_tax' => $totalTax,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
