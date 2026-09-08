<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Services\Accounting\ActualsService;
use Illuminate\Support\Carbon;

class BudgetVsActualAnalyticsService
{
    public function calculate(int $companyId, array $period, ?int $branchId = null): array
    {
        $budgets = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->with('lines.account')
            ->get();

        if ($budgets->isEmpty()) {
            return [
                'has_budget' => false,
                'lines' => [],
                'total_budgeted' => 0,
                'total_actual' => 0,
                'total_variance' => 0,
                'overall_utilization' => 0,
            ];
        }

        $actuals = new ActualsService();

        $fy = $budgets->first()->fiscalYear;
        $fyStart = $fy?->start_date ? Carbon::parse($fy->start_date) : Carbon::parse($period['to'])->startOfYear();
        $fyEnd = $fy?->end_date ? Carbon::parse($fy->end_date) : Carbon::parse($period['to'])->endOfYear();

        $from = max($period['from'], $fyStart->format('Y-m-d'));
        $to = min($period['to'], $fyEnd->format('Y-m-d'));

        $elapsed = max(1, min(12, $fyStart->diffInMonths(Carbon::parse($to)) + 1));

        $lines = [];
        $totalBudgeted = 0;
        $totalActual = 0;

        foreach ($budgets as $budget) {
            foreach ($budget->lines as $line) {
                $annual = (float) $line->annual_amount;
                $budgeted = $annual * ($elapsed / 12);
                $actual = $actuals->annualActual($line, $from, $to);

                $isIncome = $line->line_type === 'income';
                $variance = $isIncome ? $actual - $budgeted : $budgeted - $actual;
                $varPct = $budgeted != 0 ? ($variance / abs($budgeted)) * 100 : 0;

                $lines[] = [
                    'budget' => $budget->name,
                    'code' => $line->account?->code,
                    'account' => $line->account?->name,
                    'type' => $isIncome ? 'income' : 'expense',
                    'budgeted' => round($budgeted, 2),
                    'actual' => round($actual, 2),
                    'variance' => round($variance, 2),
                    'var_pct' => round($varPct, 1),
                    'status' => $variance >= 0 ? 'ok' : 'over',
                ];

                $totalBudgeted += $budgeted;
                $totalActual += $actual;
            }
        }

        $totalVariance = $totalBudgeted - $totalActual;

        return [
            'has_budget' => true,
            'can_create' => \App\Models\Budget::where('company_id', $companyId)->count() > 0,
            'lines' => $lines,
            'total_budgeted' => $totalBudgeted,
            'total_actual' => $totalActual,
            'total_variance' => $totalVariance,
            'overall_utilization' => $totalBudgeted > 0 ? round(($totalActual / $totalBudgeted) * 100, 1) : 0,
        ];
    }
}