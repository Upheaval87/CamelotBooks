<?php

namespace App\Services\BI;

use App\Models\BiExpenseClass;
use App\Services\BI\Concerns\BiPeriodMetrics;

class BreakEvenService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $totals = $this->statementTotals($companyId, $dateFrom, $dateTo, $branchId);
        $expenseRows = $this->expenseByAccount($companyId, $dateFrom, $dateTo, $branchId);
        $revenue = $totals['revenue'];
        $cogs = $totals['cogs'];

        $saved = BiExpenseClass::where('company_id', $companyId)->get()
            ->keyBy(fn ($r) => (int) $r->account_id);

        $varKeywords = array_map(fn ($k) => strtolower($k), config('bi.expense_classes.variable_keywords', []));
        $fixedKeywords = array_map(fn ($k) => strtolower($k), config('bi.expense_classes.fixed_keywords', []));

        $fixedCost = 0.0;
        $variableCost = 0.0;
        $classified = [];

        foreach ($expenseRows as $row) {
            $amount = (float) $row['amount'];
            $savedClass = $saved->get((int) $row['account_id'])?->class;

            if ($savedClass === 'fixed') {
                $fixedCost += $amount;
                $variableCost += 0;
                $classification = 'fixed';
            } elseif ($savedClass === 'variable') {
                $fixedCost += 0;
                $variableCost += $amount;
                $classification = 'variable';
            } else {
                $name = strtolower($row['account_name']);
                $isFixed = false;
                foreach ($fixedKeywords as $kw) {
                    if (str_contains($name, $kw)) {
                        $isFixed = true;
                        break;
                    }
                }
                if ($isFixed) {
                    $fixedCost += $amount;
                    $classification = 'fixed';
                } else {
                    $variableCost += $amount;
                    $classification = 'variable';
                }
            }

            $classified[] = [
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'amount' => $amount,
                'classification' => $classification,
            ];
        }

        $contribution = $revenue - $variableCost;
        $cmRatio = $revenue > 0 ? $contribution / $revenue : 0;

        // Weighted-average contribution per unit from product economics.
        $cmPerUnit = $this->contributionPerUnit($companyId, $dateFrom, $dateTo, $branchId);

        return [
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'variable_cost' => round($variableCost, 2),
            'fixed_cost' => round($fixedCost, 2),
            'contribution' => round($contribution, 2),
            'cm_ratio_pct' => round($cmRatio * 100, 1),
            'break_even_revenue' => $cmRatio > 0 ? round($fixedCost / $cmRatio, 2) : null,
            'break_even_units' => $cmPerUnit > 0 ? round($fixedCost / $cmPerUnit, 2) : null,
            'avg_contribution_per_unit' => round($cmPerUnit, 2),
            'classified' => $classified,
        ];
    }

    protected function contributionPerUnit(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): float
    {
        $service = new ProductAbcService();
        $result = $service->calculate($companyId, $dateFrom, $dateTo, $branchId);
        $totalQty = array_sum(array_column($result['rows'], 'quantity'));
        $totalContribution = array_sum(array_column($result['rows'], 'contribution'));
        return $totalQty > 0 ? $totalContribution / $totalQty : 0.0;
    }
}