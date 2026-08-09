<?php

namespace App\Services\Reporting;

use App\Models\SalesReceipt;

class SalesReceiptsCashbookService
{
    /**
     * Cashbook-style listing of posted sales receipts for a period.
     *
     * @return array{rows: array, total: float, date_from: ?string, date_to: ?string}
     */
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = SalesReceipt::forCompany($companyId)
            ->with(['customer', 'payments.paymentMethod'])
            ->where('status', SalesReceipt::STATUS_POSTED);

        if ($dateFrom) {
            $query->where('receipt_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('receipt_date', '<=', $dateTo);
        }

        $receipts = $query->orderBy('receipt_date')->orderBy('id')->get();

        $rows = [];
        $total = 0.0;
        foreach ($receipts as $r) {
            $rows[] = [
                'date' => $r->receipt_date?->format('Y-m-d'),
                'receipt_number' => $r->receipt_number,
                'customer_name' => $r->customer->name ?? 'Walk-in',
                'method' => $r->payments->first()?->paymentMethod?->name ?? '—',
                'reference' => $r->reference ?? '—',
                'total' => (float) $r->total,
            ];
            $total += (float) $r->total;
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
