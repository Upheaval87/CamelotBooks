<?php

namespace App\Services\Accounting\FixedAssets\Calculators;

use App\Services\Accounting\FixedAssets\DepreciationMethodInterface;

class DoubleDecliningCalculator implements DepreciationMethodInterface
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
        if ($usefulLife <= 0) {
            return 0.0;
        }

        $rate = 2 / $usefulLife;
        $netBookValue = $currentCost - $accumulatedDepreciation - $accumulatedImpairment;
        $charge = $netBookValue * $rate;

        $remaining = $netBookValue - $residualValue;

        if ($remaining <= 0) {
            return 0.0;
        }

        return (float) number_format(max(0, min($charge, $remaining)), 2, '.', '');
    }

    public function name(): string
    {
        return 'Double Declining Balance';
    }
}
