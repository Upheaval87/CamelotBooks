<?php

namespace App\Services\Reporting;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class UnbilledDeliveriesService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $quotations = Quotation::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'accepted'])
            ->whereNull('converted_invoice_id')
            ->whereNull('converted_receipt_id')
            ->when($dateFrom, fn ($q) => $q->where('quotation_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('quotation_date', '<=', $dateTo))
            ->with(['customer', 'lines.product'])
            ->orderBy('quotation_date')
            ->get();

        $lines = [];
        $totalUndelivered = 0;

        foreach ($quotations as $quote) {
            foreach ($quote->lines as $line) {
                $amount = (float) $line->line_total;
                $totalUndelivered += $amount;
                $lines[] = [
                    'quotation_number' => $quote->quotation_number,
                    'date' => $quote->quotation_date,
                    'valid_until' => $quote->valid_until,
                    'customer' => $quote->customer->name ?? 'N/A',
                    'product' => $line->product->name ?? 'N/A',
                    'sku' => $line->product->sku ?? '',
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'line_total' => $amount,
                    'status' => $quote->status,
                ];
            }
        }

        return [
            'lines' => $lines,
            'total_undelivered' => $totalUndelivered,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
