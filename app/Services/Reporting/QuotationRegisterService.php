<?php

namespace App\Services\Reporting;

use App\Models\Quotation;

class QuotationRegisterService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Quotation::forCompany($companyId)
            ->with('customer');

        if ($dateFrom) {
            $query->where('quotation_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('quotation_date', '<=', $dateTo);
        }

        $quotations = $query->orderBy('quotation_date')->orderBy('id')->get();

        $rows = [];
        $total = 0.0;
        foreach ($quotations as $q) {
            $rows[] = [
                'quotation_number' => $q->quotation_number,
                'customer_name' => $q->customer->name ?? 'N/A',
                'date' => $q->quotation_date?->format('Y-m-d'),
                'valid_until' => $q->valid_until?->format('Y-m-d'),
                'total' => (float) $q->total,
                'status' => $q->status,
            ];
            $total += (float) $q->total;
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
