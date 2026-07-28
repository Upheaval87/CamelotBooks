<?php

namespace App\Services\Reporting;

use App\Models\Quotation;

class QuotationStatusService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Quotation::forCompany($companyId)
            ->with('customer')
            ->where('status', '!=', Quotation::STATUS_VOID);

        if ($dateFrom) {
            $query->where('quotation_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('quotation_date', '<=', $dateTo);
        }

        $quotations = $query->orderBy('quotation_date', 'desc')->get();

        $results = [];
        foreach ($quotations as $q) {
            $results[] = [
                'quotation_number' => $q->quotation_number,
                'date' => $q->quotation_date,
                'valid_until' => $q->valid_until,
                'customer_name' => $q->customer->name ?? 'N/A',
                'total' => (float) $q->total,
                'status' => $q->status,
                'converted_invoice_id' => $q->converted_invoice_id,
                'converted_receipt_id' => $q->converted_receipt_id,
                'is_expired' => $q->valid_until && $q->valid_until->isPast() && !in_array($q->status, ['accepted', 'converted', 'void']),
            ];
        }

        $summary = [
            'total' => count($results),
            'draft' => count(array_filter($results, fn ($r) => $r['status'] === 'draft')),
            'sent' => count(array_filter($results, fn ($r) => $r['status'] === 'sent')),
            'accepted' => count(array_filter($results, fn ($r) => $r['status'] === 'accepted')),
            'declined' => count(array_filter($results, fn ($r) => $r['status'] === 'declined')),
            'converted' => count(array_filter($results, fn ($r) => $r['status'] === 'converted')),
            'expired' => count(array_filter($results, fn ($r) => $r['is_expired'])),
        ];

        return [
            'quotations' => $results,
            'summary' => $summary,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
