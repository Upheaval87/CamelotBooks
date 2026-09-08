<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\Reporting\AgingReportService;
use App\Services\Reporting\Analytics\BranchesAnalyticsService;
use App\Services\Reporting\Analytics\BudgetVsActualAnalyticsService;
use App\Services\Reporting\Analytics\CashFlowProjectionService;
use App\Services\Reporting\Analytics\CustomerAnalyticsService;
use App\Services\Reporting\Analytics\ExpenseAnalyticsService;
use App\Services\Reporting\Analytics\FinancialRatiosService;
use App\Services\Reporting\Analytics\ForecastAnalyticsService;
use App\Services\Reporting\Analytics\InventoryAnalyticsService;
use App\Services\Reporting\Analytics\OverviewAnalyticsService;
use App\Services\Reporting\Analytics\ProfitabilityAnalyticsService;
use App\Services\Reporting\Analytics\PurchasingAnalyticsService;
use App\Services\Reporting\Analytics\RevenueExpenseTrendService;
use App\Services\Reporting\Analytics\SalesAnalyticsService;
use App\Services\Reporting\Analytics\TaxAnalyticsService;
use App\Services\Reporting\Analytics\WorkingCapitalAnalyticsService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    private const PAGES = [
        'overview' => 'Overview',
        'financial-ratios' => 'Financial Ratios',
        'revenue-expense-trends' => 'Revenue & Expense',
        'sales' => 'Sales',
        'profitability' => 'Profitability',
        'cash-flow-trend' => 'Cash Flow',
        'purchasing' => 'Purchasing',
        'inventory' => 'Inventory',
        'expenses' => 'Expenses',
        'customers' => 'Customers',
        'working-capital' => 'Working Capital',
        'budget-vs-actual' => 'Budget vs Actual',
        'tax' => 'Tax',
        'forecasts' => 'Forecasts',
        'branches' => 'Branches',
    ];

    public function overview()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new OverviewAnalyticsService())->calculate($companyId, $period, $this->branchId(), $this->costCenterId());

        return $this->render('overview', $period, [
            'data' => $data,
        ]);
    }

    public function financialRatios()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new FinancialRatiosService())->calculate($companyId, $period['as_of'], $this->branchId(), $this->costCenterId());

        return $this->render('financial-ratios', $period, [
            'data' => $data,
        ]);
    }

    public function revenueExpenseTrends()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $periods = (int) request('periods', 12);
        $dimension = request('dimension', 'none');

        $trailingFrom = Carbon::parse($period['to'])->subMonths(11)->startOfMonth()->format('Y-m-d');

        $data = (new RevenueExpenseTrendService())->calculate(
            $companyId,
            $trailingFrom,
            $period['to'],
            $periods,
            $this->branchId(),
            $this->costCenterId(),
            $dimension
        );

        return $this->render('revenue-expense-trends', $period, [
            'data' => $data,
            'dimension' => $dimension,
        ]);
    }

    public function sales()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new SalesAnalyticsService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());

        return $this->render('sales', $period, [
            'data' => $data,
        ]);
    }

    public function purchasing()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new PurchasingAnalyticsService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());

        return $this->render('purchasing', $period, [
            'data' => $data,
        ]);
    }

    public function inventory()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $slowMovingDays = (int) request('slow_moving_days', 90);

        $data = (new InventoryAnalyticsService())->calculate($companyId, $period['as_of'], $period['from'], $period['to'], $slowMovingDays);

        return $this->render('inventory', $period, [
            'data' => $data,
            'slowMovingDays' => $slowMovingDays,
        ]);
    }

    public function profitability()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new ProfitabilityAnalyticsService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $this->costCenterId());

        return $this->render('profitability', $period, [
            'data' => $data,
        ]);
    }

    public function cashFlowTrend()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $projectionMonths = (int) request('projection_months', 6);

        $data = (new CashFlowProjectionService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $projectionMonths);

        return $this->render('cash-flow-trend', $period, [
            'data' => $data,
            'projectionMonths' => $projectionMonths,
        ]);
    }

    public function expenses()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new ExpenseAnalyticsService())->calculate($companyId, $period, $this->branchId(), $this->costCenterId());

        return $this->render('expenses', $period, [
            'data' => $data,
        ]);
    }

    public function customers()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new CustomerAnalyticsService())->calculate($companyId, $period, $this->branchId());

        return $this->render('customers', $period, [
            'data' => $data,
        ]);
    }

    public function workingCapital()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new WorkingCapitalAnalyticsService())->calculate($companyId, $period, $this->branchId(), $this->costCenterId());

        return $this->render('working-capital', $period, [
            'data' => $data,
        ]);
    }

    public function budgetVsActual()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new BudgetVsActualAnalyticsService())->calculate($companyId, $period, $this->branchId());

        return $this->render('budget-vs-actual', $period, [
            'data' => $data,
        ]);
    }

    public function tax()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new TaxAnalyticsService())->calculate($companyId, $period, $this->branchId());

        return $this->render('tax', $period, [
            'data' => $data,
        ]);
    }

    public function forecasts()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new ForecastAnalyticsService())->calculate($companyId, $period, $this->branchId());

        return $this->render('forecasts', $period, [
            'data' => $data,
        ]);
    }

    public function branches()
    {
        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = (new BranchesAnalyticsService())->calculate($companyId, $period, $this->costCenterId());

        return $this->render('branches', $period, [
            'data' => $data,
        ]);
    }

    public function export()
    {
        $page = (string) request('page', 'overview');
        if (!array_key_exists($page, self::PAGES)) {
            abort(404);
        }

        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $rows = $this->exportRows($page, $companyId, $period);

        $filename = 'analytics-' . str_replace('_', '-', $page) . '-' . $period['from'] . '-to-' . $period['to'] . '.csv';

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Metric', 'Value', 'Period From', 'Period To']);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function printPage()
    {
        $page = (string) request('page', 'overview');
        if (!array_key_exists($page, self::PAGES)) {
            abort(404);
        }

        $companyId = $this->companyId();
        $period = $this->resolvePeriod();
        $data = $this->pageData($page, $companyId, $period);

        return view('analytics.print', [
            'page' => $page,
            'pageLabel' => self::PAGES[$page],
            'period' => $period,
            'data' => $data,
            'cur' => $this->currency(),
        ]);
    }

    private function render(string $page, array $period, array $extra = [])
    {
        $companyId = $this->companyId();

        return view('analytics.' . $page, array_merge($extra, [
            'period' => $period,
            'pages' => self::PAGES,
            'activePage' => $page,
            'cur' => $this->currency(),
            'branches' => \App\Models\Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => \App\Models\CostCenter::forCompany($companyId)->active()->orderBy('name')->get(),
        ]));
    }

    private function companyId(): int
    {
        return (int) session('current_company_id');
    }

    private function branchId(): ?int
    {
        $v = request('branch_id');
        return $v ? (int) $v : null;
    }

    private function costCenterId(): ?int
    {
        $v = request('cost_center_id');
        return $v ? (int) $v : null;
    }

    private function currency(): array
    {
        $companyId = $this->companyId();
        $settings = SystemSetting::getMany('currency', $companyId);

        return [
            'symbol' => (string) ($settings['currency_symbol'] ?? '$'),
            'decimals' => (int) ($settings['decimal_places'] ?? 2),
            'base' => (string) ($settings['base_currency'] ?? 'MWK'),
        ];
    }

    private function resolvePeriod(): array
    {
        $key = (string) request('period', 'ytd');
        if (!in_array($key, ['month', 'quarter', 'ytd'])) {
            $key = 'ytd';
        }

        $now = Carbon::now();

        switch ($key) {
            case 'month':
                $from = $now->copy()->startOfMonth();
                $to = $now->copy();
                $prevFrom = $from->copy()->subMonth()->startOfMonth();
                $prevTo = $from->copy()->subMonth()->endOfMonth();
                $label = 'This Month';
                break;
            case 'quarter':
                $from = $now->copy()->startOfQuarter();
                $to = $now->copy();
                $prevFrom = $from->copy()->subQuarter()->startOfQuarter();
                $prevTo = $from->copy()->subQuarter()->endOfQuarter();
                $label = 'This Quarter';
                break;
            default:
                $from = $now->copy()->startOfYear();
                $to = $now->copy();
                $prevTo = $to->copy()->subYear();
                $prevFrom = $prevTo->copy()->startOfYear();
                $label = 'Year to Date';
        }

        return [
            'key' => $key,
            'label' => $label,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'prev_from' => $prevFrom->format('Y-m-d'),
            'prev_to' => $prevTo->format('Y-m-d'),
            'as_of' => $to->format('Y-m-d'),
        ];
    }

    private function pageData(string $page, int $companyId, array $period): array
    {
        switch ($page) {
            case 'overview':
                return (new OverviewAnalyticsService())->calculate($companyId, $period, $this->branchId(), $this->costCenterId());
            case 'financial-ratios':
                return (new FinancialRatiosService())->calculate($companyId, $period['as_of'], $this->branchId(), $this->costCenterId());
            case 'revenue-expense-trends':
                $from = Carbon::parse($period['to'])->subMonths(11)->startOfMonth()->format('Y-m-d');
                return (new RevenueExpenseTrendService())->calculate($companyId, $from, $period['to'], (int) request('periods', 12), $this->branchId(), $this->costCenterId(), request('dimension', 'none'));
            case 'sales':
                return (new SalesAnalyticsService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'purchasing':
                return (new PurchasingAnalyticsService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'inventory':
                return (new InventoryAnalyticsService())->calculate($companyId, $period['as_of'], $period['from'], $period['to'], (int) request('slow_moving_days', 90));
            case 'profitability':
                return (new ProfitabilityAnalyticsService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $this->costCenterId());
            case 'cash-flow-trend':
                return (new CashFlowProjectionService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), (int) request('projection_months', 6));
            case 'expenses':
                return (new ExpenseAnalyticsService())->calculate($companyId, $period, $this->branchId(), $this->costCenterId());
            case 'customers':
                return (new CustomerAnalyticsService())->calculate($companyId, $period, $this->branchId());
            case 'working-capital':
                return (new WorkingCapitalAnalyticsService())->calculate($companyId, $period, $this->branchId(), $this->costCenterId());
            case 'budget-vs-actual':
                return (new BudgetVsActualAnalyticsService())->calculate($companyId, $period, $this->branchId());
            case 'tax':
                return (new TaxAnalyticsService())->calculate($companyId, $period, $this->branchId());
            case 'forecasts':
                return (new ForecastAnalyticsService())->calculate($companyId, $period, $this->branchId());
            case 'branches':
                return (new BranchesAnalyticsService())->calculate($companyId, $period, $this->costCenterId());
        }

        return [];
    }

    private function exportRows(string $page, int $companyId, array $period): array
    {
        $data = $this->pageData($page, $companyId, $period);
        $rows = [];

        $add = function (string $metric, $value) use (&$rows, $period) {
            $rows[] = [$metric, is_numeric($value) ? number_format((float) $value, 2, '.', '') : (string) $value, $period['from'], $period['to']];
        };

        switch ($page) {
            case 'overview':
                foreach (($data['kpis'] ?? []) as $key => $kpi) {
                    $add(ucwords(str_replace('_', ' ', $key)), $kpi['value'] ?? 0);
                }
                break;
            case 'financial-ratios':
                foreach (($data['ratios'] ?? []) as $group => $ratios) {
                    foreach ($ratios as $key => $ratio) {
                        $add(ucwords(str_replace('_', ' ', $key)), $ratio['value'] ?? 0);
                    }
                }
                break;
            case 'revenue-expense-trends':
                foreach (($data['results'] ?? []) as $row) {
                    $add($row['period'] . ' revenue', $row['revenue'] ?? 0);
                    $add($row['period'] . ' expense', $row['expense'] ?? 0);
                }
                break;
            case 'sales':
                $add('Total revenue', $data['revenue']['total_income'] ?? 0);
                $add('Invoice count', $data['invoice_count'] ?? 0);
                foreach (($data['top_customers'] ?? []) as $row) {
                    $add('Top customer: ' . ($row['customer_name'] ?? '?'), $row['total_revenue'] ?? 0);
                }
                break;
            case 'purchasing':
                $add('Total purchases', $data['totals']['purchase_total'] ?? 0);
                break;
            case 'inventory':
                $add('Total stock value', $data['current_value']['total_value'] ?? 0);
                $add('Item count', $data['current_value']['item_count'] ?? 0);
                break;
            case 'profitability':
                $add('Gross margin', $data['gross_profit'] ?? 0);
                break;
            case 'cash-flow-trend':
                $add('Net cash flow', $data['total_net_cash_flow'] ?? 0);
                break;
            case 'expenses':
                foreach (($data['kpis'] ?? []) as $key => $kpi) {
                    $add(ucwords(str_replace('_', ' ', $key)), $kpi['value'] ?? 0);
                }
                break;
            case 'customers':
                foreach (($data['kpis'] ?? []) as $key => $kpi) {
                    $add(ucwords(str_replace('_', ' ', $key)), $kpi['value'] ?? 0);
                }
                break;
            case 'working-capital':
                foreach (($data['kpis'] ?? []) as $key => $kpi) {
                    $add(ucwords(str_replace('_', ' ', $key)), $kpi['value'] ?? 0);
                }
                break;
            case 'budget-vs-actual':
                $add('Total budgeted', $data['total_budgeted'] ?? 0);
                $add('Total actual', $data['total_actual'] ?? 0);
                $add('Utilization %', $data['overall_utilization'] ?? 0);
                break;
            case 'tax':
                foreach (($data['kpis'] ?? []) as $key => $kpi) {
                    $add(ucwords(str_replace('_', ' ', $key)), $kpi['value'] ?? 0);
                }
                break;
            case 'forecasts':
                $add('Opening cash', $data['opening_cash'] ?? 0);
                $add('Projected next quarter', $data['q4']['next_quarter'] ?? 0);
                break;
            case 'branches':
                foreach (($data['rows'] ?? []) as $row) {
                    $add('Branch ' . ($row['name'] ?? '?') . ' revenue', $row['revenue'] ?? 0);
                    $add('Branch ' . ($row['name'] ?? '?') . ' profit', $row['profit'] ?? 0);
                }
                break;
        }

        return $rows;
    }
}