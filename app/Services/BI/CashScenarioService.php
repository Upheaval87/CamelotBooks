<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\BiPeriodMetrics;
use App\Services\Reporting\AgingReportService;
use Carbon\Carbon;

class CashScenarioService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null, ?array $scenarioConfig = null): array
    {
        $startOfWeek = Carbon::parse($dateFrom)->startOfWeek();

        $cash = $this->cashAndBankAsOf($companyId, $dateFrom);
        $opening = $cash['total'];

        $aging = new AgingReportService();
        $ar = $aging->arAging($companyId, $branchId, $dateTo);
        $ap = $aging->apAging($companyId, $branchId, $dateTo);

        // Trailing net cash flow (P&L estimate) averaged to a weekly figure.
        $trailingMonth = 90;
        $netTrailing = $this->netOperatingCash($companyId, $startOfWeek->copy()->subDays($trailingMonth), $startOfWeek->copy()->subDay(), $branchId);
        $weeklyBase = $netTrailing / max(1, round($trailingMonth / 7));

        // Collections ladder for outstanding receivables (weeks 1-13).
        $arLadder = $this->arCollectionLadder($ar['totals']);

        // Payables falling due (week 1 = current bucket).
        $apLadder = $this->apDueLadder($ap['totals']);

        $scenarios = [];
        $labels = ['bull' => 'Bull (optimistic)', 'base' => 'Base (most likely)', 'bear' => 'Bear (conservative)'];
        $defaults = $scenarioConfig ?? config('bi.defaults.scenario', []);
        foreach (['bull', 'base', 'bear'] as $key) {
            $inflowM = (float) ($defaults[$key]['inflow'] ?? 1.0);
            $outflowM = (float) ($defaults[$key]['outflow'] ?? 1.0);
            $weeks = [];
            $openingWk = $opening;
            for ($w = 1; $w <= 13; $w++) {
                $inflow = $weeklyBase * $inflowM;
                if (isset($arLadder[$w])) {
                    $inflow += $arLadder[$w];
                }
                $outflow = $weeklyBase * $outflowM + (float) ($apLadder[1] ?? 0) / 13;
                $net = $inflow - $outflow;
                $closing = $openingWk + $net;
                $weeks[] = [
                    'week' => $w,
                    'label' => $startOfWeek->copy()->addWeeks($w - 1)->format('M d'),
                    'opening' => round($openingWk, 2),
                    'inflow' => round($inflow, 2),
                    'outflow' => round($outflow, 2),
                    'net_cash_flow' => round($net, 2),
                    'closing' => round($closing, 2),
                ];
                $openingWk = $closing;
            }
            $scenarios[$key] = [
                'key' => $key,
                'label' => $labels[$key],
                'weeks' => $weeks,
                'final_balance' => round($openingWk, 2),
            ];
        }

        return [
            'opening_cash' => round($opening, 2),
            'weekly_base' => round($weeklyBase, 2),
            'scenarios' => array_values($scenarios),
            'date_from' => $dateFrom,
        ];
    }

    protected function netOperatingCash(int $companyId, string $from, string $to, ?int $branchId): float
    {
        $totals = $this->statementTotals($companyId, $from, $to, $branchId);
        return $totals['revenue'] - $totals['total_expenses'];
    }

    protected function arCollectionLadder(array $ar): array
    {
        $ladder = [];
        // current -> collected ~60% in week 1, 25% week 2, 15% week 3
        $ladder[1] = (float) ($ar['current'] ?? 0) * 0.60 + (float) ($ar['days_1_30'] ?? 0) * 0.10;
        $ladder[2] = (float) ($ar['current'] ?? 0) * 0.25 + (float) ($ar['days_1_30'] ?? 0) * 0.20;
        $ladder[3] = (float) ($ar['current'] ?? 0) * 0.15 + (float) ($ar['days_1_30'] ?? 0) * 0.30;
        $ladder[4] = (float) ($ar['days_1_30'] ?? 0) * 0.25;
        $ladder[5] = (float) ($ar['days_31_60'] ?? 0) * 0.35;
        $ladder[6] = (float) ($ar['days_31_60'] ?? 0) * 0.35;
        $ladder[8] = (float) ($ar['days_61_90'] ?? 0) * 0.25;
        $ladder[10] = (float) ($ar['days_61_90'] ?? 0) * 0.25;
        $ladder[13] = (float) ($ar['days_90_plus'] ?? 0) * 0.10;
        return $ladder;
    }

    protected function apDueLadder(array $ap): array
    {
        return [
            1 => (float) ($ap['current'] ?? 0) * 0.35,
            2 => (float) ($ap['current'] ?? 0) * 0.35,
            3 => (float) ($ap['current'] ?? 0) * 0.30,
        ];
    }
}