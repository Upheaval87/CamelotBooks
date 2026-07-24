<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\BalanceSheetService;
use App\Services\Reporting\IncomeStatementService;
use App\Services\Reporting\AgingReportService;
use App\Models\SystemSetting;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialRatiosService
{
    private const TARGET_GROUP = 'ratio_targets';

    private BalanceSheetService $balanceSheet;
    private IncomeStatementService $incomeStatement;
    private AgingReportService $agingReport;

    public function __construct()
    {
        $this->incomeStatement = new IncomeStatementService();
        $this->balanceSheet = new BalanceSheetService($this->incomeStatement);
        $this->agingReport = new AgingReportService();
    }

    public function calculate(int $companyId, string $asOfDate, ?int $branchId = null, ?int $costCenterId = null): array
    {
        $bs = $this->balanceSheet->generate($companyId, $branchId, $asOfDate);

        $fyStart = $this->getFiscalYearStart($companyId, $asOfDate);
        $is = $this->incomeStatement->generate($companyId, $branchId, $fyStart, $asOfDate, null, $costCenterId);

        $arAging = $this->agingReport->arAging($companyId, $branchId, $asOfDate);
        $apAging = $this->agingReport->apAging($companyId, $branchId, $asOfDate);

        $targets = SystemSetting::getMany(self::TARGET_GROUP, $companyId);

        $totalAssets = $bs['total_assets'];
        $totalLiabilities = $bs['total_liabilities'];
        $totalEquity = $bs['total_equity'];

        $currentAssets = $this->sumSubType($bs['groups'], 'asset', 'current_asset');
        $nonCurrentAssets = $this->sumSubType($bs['groups'], 'asset', 'non_current_asset');
        $currentLiabilities = $this->sumSubType($bs['groups'], 'liability', 'current_liability');

        $inventoryValue = $this->sumSubType($bs['groups'], 'asset', 'current_asset');

        $totalAR = $arAging['totals']['total'] ?? 0;
        $totalAP = $apAging['totals']['total'] ?? 0;

        $totalRevenue = $is['total_income'];
        $netIncome = $is['net_income'];
        $totalExpenses = $is['total_expenses'];
        $cogs = $this->sumExpenseSubType($is['groups'], 'cost_of_goods_sold');
        $grossProfit = $totalRevenue - $cogs;

        $ratios = [];

        $workingCapital = $currentAssets - $currentLiabilities;

        $ratios['liquidity'] = [
            'current_ratio' => $this->ratio($currentAssets, $currentLiabilities),
            'quick_ratio' => $this->ratio($currentAssets - $inventoryValue, $currentLiabilities),
            'working_capital' => ['value' => $workingCapital, 'target' => null],
        ];

        $ratios['profitability'] = [
            'gross_margin' => $this->ratio($grossProfit, $totalRevenue),
            'net_margin' => $this->ratio($netIncome, $totalRevenue),
            'roa' => $this->ratio($netIncome, $totalAssets),
            'roe' => $this->ratio($netIncome, $totalEquity),
        ];

        $daysInPeriod = Carbon::parse($fyStart)->diffInDays(Carbon::parse($asOfDate)) ?: 365;

        $arTurnover = $this->ratio($totalRevenue, $totalAR);
        $apTurnover = $this->ratio($totalExpenses, $totalAP);

        $ratios['efficiency'] = [
            'ar_turnover' => $arTurnover,
            'dso' => $this->daysOutstanding($arTurnover, $daysInPeriod),
            'ap_turnover' => $apTurnover,
            'dpo' => $this->daysOutstanding($apTurnover, $daysInPeriod),
            'inventory_turnover' => $this->ratio($cogs, $inventoryValue),
            'dio' => $this->daysOutstanding($this->ratio($cogs, $inventoryValue), $daysInPeriod),
            'cash_conversion_cycle' => null,
        ];

        $dso = $ratios['efficiency']['dso']['value'] ?? null;
        $dio = $ratios['efficiency']['dio']['value'] ?? null;
        $dpo = $ratios['efficiency']['dpo']['value'] ?? null;
        if ($dso !== null && $dio !== null && $dpo !== null) {
            $ratios['efficiency']['cash_conversion_cycle'] = [
                'value' => $dso + $dio - $dpo,
                'target' => null,
                'unit' => 'days',
            ];
        }

        $ratios['leverage'] = [
            'debt_to_equity' => $this->ratio($totalLiabilities, $totalEquity),
            'debt_to_assets' => $this->ratio($totalLiabilities, $totalAssets),
        ];

        foreach ($ratios as $category => &$items) {
            foreach ($items as $key => &$item) {
                if (!is_array($item)) continue;
                if (isset($targets[$key])) {
                    $item['target'] = (float) $targets[$key];
                }
            }
        }

        return [
            'ratios' => $ratios,
            'as_of_date' => $asOfDate,
            'summary' => [
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'current_assets' => $currentAssets,
                'current_liabilities' => $currentLiabilities,
                'inventory_value' => $inventoryValue,
                'total_revenue' => $totalRevenue,
                'net_income' => $netIncome,
                'gross_profit' => $grossProfit,
            ],
        ];
    }

    private function ratio(float $numerator, float $denominator): ?array
    {
        if (abs($denominator) < 0.001) {
            return null;
        }
        return ['value' => $numerator / $denominator, 'target' => null];
    }

    private function daysOutstanding(?array $turnover, int $daysInPeriod): ?array
    {
        if ($turnover === null || $turnover['value'] < 0.001) {
            return null;
        }
        return ['value' => $daysInPeriod / $turnover['value'], 'target' => null, 'unit' => 'days'];
    }

    private function sumSubType(array $groups, string $type, string $subType): float
    {
        $total = 0;
        if (isset($groups[$type][$subType])) {
            foreach ($groups[$type][$subType] as $item) {
                $total += abs($item['balance']);
            }
        }
        return $total;
    }

    private function sumExpenseSubType(array $groups, string $subType): float
    {
        $total = 0;
        if (isset($groups['expense'][$subType])) {
            foreach ($groups['expense'][$subType] as $item) {
                $total += abs($item['net'] ?? 0);
            }
        }
        return $total;
    }

    private function getFiscalYearStart(int $companyId, string $asOfDate): string
    {
        $company = Company::find($companyId);
        $fyStartMonth = $company->fiscal_year_start_month ?? 1;
        $date = Carbon::parse($asOfDate);
        if ($date->month >= $fyStartMonth) {
            return $date->startOfYear()->addMonths($fyStartMonth - 1)->format('Y-m-d');
        }
        return $date->subYear()->startOfYear()->addMonths($fyStartMonth - 1)->format('Y-m-d');
    }
}
