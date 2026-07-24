<?php

namespace App\Services\Accounting\FixedAssets\Calculators;

use App\Services\Accounting\FixedAssets\DepreciationMethodInterface;

class SumOfYearsDigitsCalculator implements DepreciationMethodInterface
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

        $sumOfYearsDigits = $usefulLife * ($usefulLife + 1) / 2;
        $remainingLife = $usefulLife - $periodNumber + 1;

        if ($remainingLife <= 0) {
            return 0.0;
        }

        $depreciableAmount = $currentCost - $residualValue;
        $charge = ($remainingLife / $sumOfYearsDigits) * $depreciableAmount;

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
        return 'Sum of Years Digits';
    }
}
