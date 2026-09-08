<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Account;
use App\Services\Reporting\IncomeStatementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ForecastAnalyticsService
{
    private const ACTUAL_WEEKS = 8;
    private const FORECAST_WEEKS = 5;

    public function calculate(int $companyId, array $period, ?int $branchId = null): array
    {
        $start = Carbon::parse($period['to'])->startOfWeek()->subWeeks(self::ACTUAL_WEEKS - 1);

        $weekly = $this->weeklyNet($companyId, $start->format('Y-m-d'), $period['to'], $branchId);

        $labels = [];
        $actual = [];
        $forecast = [];
        $bandPlus = [];
        $bandMinus = [];

        for ($i = 0; $i < self::ACTUAL_WEEKS + self::FORECAST_WEEKS; $i++) {
            $weekStart = $start->copy()->addWeeks($i);
            $labels[] = $weekStart->format('M j');

            if ($i < self::ACTUAL_WEEKS) {
                $actual[] = $weekly[$i] ?? 0;
                $forecast[] = null;
                $bandPlus[] = null;
                $bandMinus[] = null;
            } else {
                $actual[] = null;
            }
        }

        $lastActuals = array_slice($actual, max(0, self::ACTUAL_WEEKS - 4), self::ACTUAL_WEEKS);
        $lastActuals = array_values(array_filter($lastActuals, fn ($v) => $v !== null));
        $mean = count($lastActuals) ? array_sum($lastActuals) / count($lastActuals) : 0;

        $variance = 0;
        foreach ($lastActuals as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $std = count($lastActuals) > 1 ? sqrt($variance / (count($lastActuals) - 1)) : 0;

        for ($i = self::ACTUAL_WEEKS; $i < self::ACTUAL_WEEKS + self::FORECAST_WEEKS; $i++) {
            $weekStart = $start->copy()->addWeeks($i);
            $seasonal = $this->seasonalAdjustment($weekly, $i % self::ACTUAL_WEEKS);
            $value = $mean * $seasonal;

            $forecast[$i] = $value;
            $bandPlus[$i] = $value + $std;
            $bandMinus[$i] = $value - $std;

            if ($i === self::ACTUAL_WEEKS) {
                $labels[$i] = $weekStart->format('M j');
            }
        }

        $opening = $this->cashBalance($companyId, $period['to']);

        $q4 = $this->quarterlySalesForecast($companyId, $branchId);

        return [
            'labels' => $labels,
            'actual' => $actual,
            'forecast' => $forecast,
            'band_plus' => $bandPlus,
            'band_minus' => $bandMinus,
            'opening_cash' => $opening,
            'mean_weekly' => $mean,
            'stddev' => $std,
            'actual_weeks' => self::ACTUAL_WEEKS,
            'forecast_weeks' => self::FORECAST_WEEKS,
            'q4' => $q4,
        ];
    }

    private function weeklyNet(int $companyId, string $from, string $to, ?int $branchId): array
    {
        $rows = DB::table('bank_transactions')
            ->where('company_id', $companyId)
            ->where('date', '>=', $from)
            ->where('date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("YEARWEEK(date, 1) as yw, SUM(amount) as total, MIN(date) as min_date")
            ->groupBy('yw')
            ->get();

        $byWeekKey = $rows->mapWithKeys(function ($row) {
            return [Carbon::parse($row->min_date)->format('Y-m-d') => (float) $row->total];
        });

        $start = Carbon::parse($from);
        $weekly = [];
        for ($i = 0; $i < self::ACTUAL_WEEKS; $i++) {
            $key = $start->copy()->addWeeks($i)->startOfWeek()->format('Y-m-d');
            $weekly[] = $byWeekKey[$key] ?? 0;
        }

        return $weekly;
    }

    private function seasonalAdjustment(array $weekly, int $index): float
    {
        if (count($weekly) < 4) {
            return 1.0;
        }

        $mean = array_sum($weekly) / count($weekly);
        if ($mean == 0) {
            return 1.0;
        }

        $value = $weekly[$index] ?? $mean;
        return max(0.5, min(1.5, $value / $mean));
    }

    private function cashBalance(int $companyId, string $asOf): float
    {
        $accounts = Account::forCompany($companyId)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_bank_account', true)->orWhere('is_petty_cash', true);
            })
            ->get();

        $ids = $accounts->pluck('id')->all();
        if (empty($ids)) {
            return 0;
        }

        $row = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.company_id', $companyId)
            ->whereIn('e.status', ['posted', 'reversed'])
            ->where('e.date', '<=', $asOf)
            ->whereIn('l.account_id', $ids)
            ->selectRaw('COALESCE(SUM(l.debit),0) as d, COALESCE(SUM(l.credit),0) as c')
            ->first();

        $opening = $accounts->sum('opening_balance');
        return ((float) $row->d - (float) $row->c) + (float) $opening;
    }

    private function quarterlySalesForecast(int $companyId, ?int $branchId): array
    {
        $now = Carbon::now();
        $is = new IncomeStatementService();

        $months = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $labels[] = $m->format('M y');
            $income = $is->generate($companyId, $branchId, $m->copy()->startOfMonth()->format('Y-m-d'), $m->copy()->endOfMonth()->format('Y-m-d'));
            $months[] = (float) ($income['total_income'] ?? 0);
        }

        [$slope, $intercept] = $this->linreg($labels, $months);

        $projected = [];
        $base = count($labels);
        for ($i = 1; $i <= 3; $i++) {
            $idx = $base + $i - 1;
            $projected[] = max(0, $intercept + $slope * ($idx + 0));
        }

        $stdev = $this->stdev($months);
        $confidence = $stdev > 0 && abs($months[0]) > 0 ? round(1 - min(0.6, $stdev / max(1, abs(end($months)))), 2) : 0.5;

        return [
            'labels' => $labels,
            'actual' => $months,
            'projected' => $projected,
            'projection_labels' => ['+1M', '+2M', '+3M'],
            'slope' => $slope,
            'confidence' => $confidence,
            'next_quarter' => array_sum($projected),
        ];
    }

    private function linreg(array $xLabels, array $y): array
    {
        $n = count($y);
        if ($n < 2) {
            return [0, $y[0] ?? 0];
        }

        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;

        foreach ($x as $i => $xv) {
            $sumXY += $xv * $y[$i];
            $sumXX += $xv * $xv;
        }

        $denom = ($n * $sumXX) - ($sumX * $sumX);
        if (abs($denom) < 0.0001) {
            return [0, $n ? $sumY / $n : 0];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denom;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        return [$slope, $intercept];
    }

    private function stdev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0;
        }
        $mean = array_sum($values) / $n;
        $variance = 0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        return sqrt($variance / ($n - 1));
    }
}