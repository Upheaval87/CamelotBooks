<?php

namespace App\Services\Reporting;

use App\Models\StockCount;

class StockCountVarianceService
{
    public function generate(int $companyId, ?int $stockCountId = null): array
    {
        if ($stockCountId) {
            $counts = StockCount::forCompany($companyId)
                ->where('id', $stockCountId)
                ->with(['lines.product', 'branch'])
                ->get();
        } else {
            $counts = StockCount::forCompany($companyId)
                ->where('status', 'completed')
                ->with(['lines.product', 'branch'])
                ->orderBy('date', 'desc')
                ->limit(20)
                ->get();
        }

        $results = [];
        foreach ($counts as $count) {
            foreach ($count->lines as $line) {
                $results[] = [
                    'count_number' => $count->count_number,
                    'date' => $count->date,
                    'branch' => $count->branch->name ?? 'All',
                    'product_name' => $line->product->name ?? 'N/A',
                    'sku' => $line->product->sku ?? '',
                    'expected' => (float) $line->expected_quantity,
                    'counted' => (float) $line->counted_quantity,
                    'variance_qty' => (float) $line->variance_quantity,
                    'unit_cost' => (float) $line->unit_cost,
                    'variance_cost' => (float) $line->variance_cost,
                ];
            }
        }

        return [
            'lines' => $results,
            'total_variance_cost' => array_sum(array_column($results, 'variance_cost')),
        ];
    }
}
