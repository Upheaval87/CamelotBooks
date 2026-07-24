<?php

namespace App\Services\Accounting\FixedAssets\Calculators;

use App\Services\Accounting\FixedAssets\DepreciationMethodInterface;

class UnitsOfProductionCalculator implements DepreciationMethodInterface
{
    public function calculatePeriodCharge(
        float $currentCost,
        float $residualValue,
        float $accumulatedDepreciation,
        float $accumulatedImpairment,
        int $usefulLife,
        int $periodNumber,
        array $extraData = []
    ): float {
        $totalEstimatedUnits = $extraData['total_estimated_units'] ?? 0;
        $unitsUsed = $extraData['units_used'] ?? 0;

        if ($totalEstimatedUnits <= 0 || $unitsUsed <= 0) {
            return 0.0;
        }

        $depreciableAmount = $currentCost - $residualValue;
        $charge = ($depreciableAmount / $totalEstimatedUnits) * $unitsUsed;

        $maxAccumulated = $depreciableAmount;
        $currentTotal = $accumulatedDepreciation + $accumulatedImpairment;
        $remaining = $maxAccumulated - $currentTotal;

        if ($remaining <= 0) {
            return 0.0;
        }

        return (float) number_format(min($charge, $remaining), 2, '.', '');
    }

    public function name(): string
    {
        return 'Units of Production';
    }
}
