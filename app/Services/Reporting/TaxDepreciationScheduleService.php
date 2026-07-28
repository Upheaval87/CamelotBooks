<?php

namespace App\Services\Reporting;

use App\Models\Asset;
use App\Models\AssetDepreciationBook;

class TaxDepreciationScheduleService
{
    public function generate(int $companyId): array
    {
        $assets = Asset::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('status', '!=', 'disposed')
            ->with(['category', 'depreciationBooks' => fn ($q) => $q->where('book_type', 'tax')])
            ->orderBy('code')
            ->get();

        $schedule = [];
        $totalCost = 0;
        $totalAccumulated = 0;
        $totalNetBookValue = 0;

        foreach ($assets as $asset) {
            $taxBook = $asset->depreciationBooks->first();
            $cost = (float) $asset->acquisition_cost;
            $accumulated = $taxBook ? (float) $taxBook->accumulated_depreciation : 0;
            $nbv = $taxBook ? (float) $taxBook->net_book_value : $cost;

            $totalCost += $cost;
            $totalAccumulated += $accumulated;
            $totalNetBookValue += $nbv;

            $schedule[] = [
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->name,
                'category' => $asset->category->name ?? '—',
                'acquisition_date' => $asset->acquisition_date,
                'cost' => $cost,
                'residual_value' => $taxBook ? (float) $taxBook->residual_value : (float) $asset->residual_value_tax,
                'useful_life' => $taxBook ? $taxBook->useful_life : $asset->useful_life_tax,
                'depreciation_method' => $taxBook ? $taxBook->depreciation_method : $asset->depreciation_method_tax,
                'depreciation_rate' => $taxBook ? (float) $taxBook->depreciation_rate : (float) $asset->depreciation_rate_tax,
                'accumulated_depreciation' => $accumulated,
                'net_book_value' => $nbv,
                'last_depreciation_date' => $taxBook?->last_depreciation_date,
            ];
        }

        return [
            'schedule' => $schedule,
            'total_cost' => $totalCost,
            'total_accumulated' => $totalAccumulated,
            'total_nbv' => $totalNetBookValue,
        ];
    }
}
