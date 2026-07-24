<?php

namespace App\Services\Accounting\FixedAssets\Calculators;

use App\Services\Accounting\FixedAssets\DepreciationMethodInterface;

class ReducingBalanceCalculator implements DepreciationMethodInterface
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
        $rate = $extraData['depreciation_rate'] ?? null;

        if ($rate === null && $usefulLife > 0) {
            $rate = 1 - pow($residualValue / $currentCost, 1 / $usefulLife);
        }

        if ($rate === null || $rate <= 0) {
            return 0.0;
        }

        $netBookValue = $currentCost - $accumulatedDepreciation - $accumulatedImpairment;
        $charge = $netBookValue * $rate;

        $minNbv = $residualValue;
        $remaining = $netBookValue - $minNbv;

        if ($remaining <= 0) {
            return 0.0;
        }

        return (float) number_format(max(0, min($charge, $remaining)), 2, '.', '');
    }

    public function name(): string
    {
        return 'Reducing Balance';
    }
}
