<?php

namespace App\Services\Reporting;

use App\Models\SalesReceipt;
use Illuminate\Support\Facades\DB;

class DailySummaryService
{
    /**
     * Daily totals of posted sales receipts for a period.
     *
     * @return array{rows: array, total: float, receipt_count: int, date_from: ?string, date_to: ?string}
     */
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = SalesReceipt::forCompany($companyId)
            ->where('status', SalesReceipt::STATUS_POSTED);

        if ($dateFrom) {
            $query->where('receipt_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('receipt_date', '<=', $dateTo);
        }

        $rows = $query->select('receipt_date', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(total), 0) as total'))
            ->groupBy('receipt_date')
            ->orderBy('receipt_date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->receipt_date?->format('Y-m-d'),
                    'count' => (int) $row->count,
                    'total' => (float) $row->total,
                ];
            })
            ->values()
            ->toArray();

        $total = array_sum(array_column($rows, 'total'));
        $receiptCount = array_sum(array_column($rows, 'count'));

        return [
            'rows' => $rows,
            'total' => $total,
            'receipt_count' => $receiptCount,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
