<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\IncomeStatementService;
use App\Services\Reporting\AgingReportService;
use App\Services\Reporting\BalanceSheetService;
use App\Services\Accounting\InventoryService;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OverviewAnalyticsService
{
    private IncomeStatementService $incomeStatement;

    public function __construct()
    {
        $this->incomeStatement = new IncomeStatementService();
    }

    public function calculate(int $companyId, array $period, ?int $branchId = null, ?int $costCenterId = null): array
    {
        $cur = $this->incomeStatement->generate($companyId, $branchId, $period['from'], $period['to'], null, $costCenterId);
        $prev = $this->incomeStatement->generate($companyId, $branchId, $period['prev_from'], $period['prev_to'], null, $costCenterId);

        $revenue = (float) ($cur['total_income'] ?? 0);
        $revenuePrev = (float) ($prev['total_income'] ?? 0);
        $net = (float) ($cur['net_income'] ?? 0);
        $netPrev = (float) ($prev['net_income'] ?? 0);
        $expense = (float) ($cur['total_expenses'] ?? 0);
        $expensePrev = (float) ($prev['total_expenses'] ?? 0);

        $margin = $revenue != 0 ? ($net / $revenue) * 100 : 0;
        $marginPrev = $revenuePrev != 0 ? ($netPrev / $revenuePrev) * 100 : 0;

        $runway = $this->cashRunwayMonths($companyId, $period['as_of']);
        $workingCapital = $this->workingCapital($companyId, $period['as_of']);

        $trend = (new RevenueExpenseTrendService())->calculate(
            $companyId,
            Carbon::parse($period['to'])->subMonths(11)->startOfMonth()->format('Y-m-d'),
            $period['to'],
            12,
            $branchId,
            $costCenterId,
            'none'
        );

        $signals = $this->signals($companyId, $period, $branchId, $costCenterId, $revenue, $net, $margin);

        return [
            'kpis' => [
                'revenue' => ['value' => $revenue, 'prev' => $revenuePrev],
                'margin' => ['value' => $margin, 'prev' => $marginPrev],
                'expense' => ['value' => $expense, 'prev' => $expensePrev],
                'runway' => ['value' => $runway],
                'working_capital' => ['value' => $workingCapital],
            ],
            'trend' => $trend,
            'signals' => $signals,
            'variants' => $this->varianceRows($companyId, $period, $branchId, $costCenterId, $cur, $prev),
        ];
    }

    private function cashRunwayMonths(int $companyId, string $asOf): ?float
    {
        $cash = $this->cashBalance($companyId, $asOf);

        $start = Carbon::parse($asOf)->subMonths(3)->startOfMonth()->format('Y-m-d');
        $end = Carbon::parse($asOf)->format('Y-m-d');
        $is = $this->incomeStatement->generate($companyId, null, $start, $end);
        $burn = (float) ($is['total_expenses'] ?? 0) / 3;

        if ($burn > 0 && $cash > 0) {
            return round($cash / $burn, 1);
        }
        return null;
    }

    private function cashBalance(int $companyId, string $asOf): float
    {
        $accounts = Account::forCompany($companyId)->where('type', 'asset')->where('sub_type', 'current_asset')
            ->where(function ($q) {
                $q->where('is_bank_account', true)->orWhere('is_petty_cash', true)->orWhereHas('children');
            })
            ->where('is_active', true)->get();

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

        $balance = (float) ($row->d ?? 0) - (float) ($row->c ?? 0);
        $opening = $accounts->sum('opening_balance') ?? 0;

        return $balance + (float) $opening;
    }

    private function workingCapital(int $companyId, string $asOf): float
    {
        $bs = (new BalanceSheetService($this->incomeStatement))->generate($companyId, null, $asOf);
        $ca = $this->sumSubType($bs['groups'], 'asset', 'current_asset');
        $cl = $this->sumSubType($bs['groups'], 'liability', 'current_liability');
        return $ca - $cl;
    }

    private function signals(int $companyId, array $period, ?int $branchId, ?int $costCenterId, float $revenue, float $net, float $margin): array
    {
        $ar = (new AgingReportService())->arAging($companyId, $branchId, $period['as_of']);
        $totalAr = $ar['totals']['total'] ?? 0;

        $cogs = $this->cogs($companyId, $branchId, $period['from'], $period['to'], $costCenterId);
        $gross = $revenue - $cogs;
        $grossMargin = $revenue != 0 ? ($gross / $revenue) * 100 : 0;

        $inventory = $this->inventoryValue($companyId, $period['as_of']);
        $stockTurns = $inventory > 0 && $revenue > 0 ? $revenue / $inventory : 0;
        $inventoryDays = $stockTurns > 0 ? 365 / $stockTurns : 0;

        $top10 = $this->topCustomerShare($companyId, $period['from'], $period['to'], $branchId, $revenue);

        $elapsedDays = Carbon::parse($period['from'])->diffInDays(Carbon::parse($period['to'])) + 1;
        $dso = $revenue > 0 && $totalAr > 0 ? ($totalAr / $revenue) * $elapsedDays : 0;

        return [
            ['label' => 'Gross Margin', 'value' => $grossMargin, 'status' => $grossMargin >= 40 ? 'ok' : 'watch', 'good_when' => __('higher is better')],
            ['label' => 'DSO', 'value' => $dso, 'status' => $dso <= 30 ? 'ok' : 'watch', 'good_when' => __('lower is better')],
            ['label' => 'Inventory Days', 'value' => $inventoryDays, 'status' => $inventoryDays <= 45 ? 'ok' : 'watch', 'good_when' => __('lower is better')],
            ['label' => 'Stock Turns', 'value' => $stockTurns, 'status' => $stockTurns >= 4 ? 'ok' : 'watch', 'good_when' => __('higher is better')],
            ['label' => 'Top-10 Customer Share', 'value' => $top10, 'status' => $top10 <= 60 ? 'ok' : 'watch', 'good_when' => __('diversify')],
            ['label' => 'Net Margin', 'value' => $margin, 'status' => $net >= 0 ? 'ok' : 'watch', 'good_when' => __('higher is better')],
        ];
    }

    private function varianceRows(int $companyId, array $period, ?int $branchId, ?int $costCenterId, array $cur, array $prev): array
    {
        return [
            [
                'label' => 'Revenue',
                'current' => (float) ($cur['total_income'] ?? 0),
                'previous' => (float) ($prev['total_income'] ?? 0),
                'good_when' => 'up',
            ],
            [
                'label' => 'Cost of Sales',
                'current' => $this->cogs($companyId, $branchId, $period['from'], $period['to'], $costCenterId),
                'previous' => $this->cogs($companyId, $branchId, $period['prev_from'], $period['prev_to'], $costCenterId),
                'good_when' => 'down',
            ],
            [
                'label' => 'Gross Profit',
                'current' => (float) ($cur['total_income'] ?? 0) - $this->cogs($companyId, $branchId, $period['from'], $period['to'], $costCenterId),
                'previous' => (float) ($prev['total_income'] ?? 0) - $this->cogs($companyId, $branchId, $period['prev_from'], $period['prev_to'], $costCenterId),
                'good_when' => 'up',
            ],
            [
                'label' => 'Operating Expenses',
                'current' => (float) ($cur['total_expenses'] ?? 0),
                'previous' => (float) ($prev['total_expenses'] ?? 0),
                'good_when' => 'down',
            ],
            [
                'label' => 'Net Income',
                'current' => (float) ($cur['net_income'] ?? 0),
                'previous' => (float) ($prev['net_income'] ?? 0),
                'good_when' => 'up',
            ],
        ];
    }

    private function cogs(int $companyId, ?int $branchId, string $from, string $to, ?int $costCenterId): float
    {
        $is = $this->incomeStatement->generate($companyId, $branchId, $from, $to, null, $costCenterId);
        $groups = $is['groups'] ?? [];
        $total = 0;
        foreach (['cost_of_goods_sold'] as $sub) {
            foreach (($groups['expense'][$sub] ?? []) as $item) {
                $total += abs($item['net'] ?? 0);
            }
        }
        return $total;
    }

    private function inventoryValue(int $companyId, string $asOf): float
    {
        $valuation = app(\App\Services\Accounting\InventoryService::class)->getValuation($companyId, $asOf);
        return array_sum(array_column($valuation, 'total_value'));
    }

    private function topCustomerShare(int $companyId, string $from, string $to, ?int $branchId, float $revenue): float
    {
        if ($revenue <= 0) {
            return 0;
        }
        $rows = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->where('invoice_date', '>=', $from)
            ->where('invoice_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('customer_id, SUM(amount) as total')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $top10 = $rows->sum('total');
        return $top10 > 0 ? ($top10 / $revenue) * 100 : 0;
    }

    private function sumSubType(array $groups, string $type, string $subType): float
    {
        $total = 0;
        foreach (($groups[$type][$subType] ?? []) as $item) {
            $total += abs($item['balance'] ?? 0);
        }
        return $total;
    }
}