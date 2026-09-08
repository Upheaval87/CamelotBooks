<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BiAction;
use App\Models\BiExpenseClass;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\SystemSetting;
use App\Services\BI\BiSettingService;
use App\Services\BI\BoardPackService;
use App\Services\BI\BranchProfitabilityService;
use App\Services\BI\BreakEvenService;
use App\Services\BI\BudgetVarianceService;
use App\Services\BI\CashScenarioService;
use App\Services\BI\CohortRetentionService;
use App\Services\BI\CustomerLifetimeValueService;
use App\Services\BI\EmployeeProductivityService;
use App\Services\BI\OverviewInsightService;
use App\Services\BI\PriceVolumeMixService;
use App\Services\BI\ProductAbcService;
use App\Services\BI\SupplierScorecardService;
use App\Services\BI\TrueTotalCostService;
use App\Services\BI\WorkingCapitalService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class BiController extends Controller
{
    private const PAGES = [
        'overview' => 'Overview & Command Centre',
        'branch' => 'Branch Profitability',
        'clv' => 'Customer Lifetime Value',
        'employee' => 'Employee Productivity',
        'truecost' => 'True Total Cost',
        'product' => 'Product ABC',
        'supplier' => 'Supplier Scorecard',
        'workcap' => 'Working Capital',
        'scenarios' => 'Cash Scenarios',
        'variance' => 'Budget Variance',
        'pvm' => 'Price-Volume-Mix',
        'breakeven' => 'Break Even',
        'cohorts' => 'Cohorts & Churn',
        'board' => 'Board Pack',
    ];

    public function overview()
    {
        $period = $this->resolvePeriod();
        $data = (new OverviewInsightService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId(), $this->costCenterId());

        return $this->render('overview', $period, ['data' => $data]);
    }

    public function branch()
    {
        $period = $this->resolvePeriod();
        $companyId = $this->companyId();
        $data = (new BranchProfitabilityService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());

        return $this->render('branch', $period, [
            'data' => $data,
            'allocationSettings' => (new BiSettingService())->getWithDefaults('allocation', $companyId),
        ]);
    }

    public function clv()
    {
        $period = $this->resolvePeriod();
        $data = (new CustomerLifetimeValueService())->calculate($this->companyId(), $this->branchId());

        return $this->render('clv', $period, ['data' => $data]);
    }

    public function employee()
    {
        $period = $this->resolvePeriod();
        $data = (new EmployeeProductivityService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('employee', $period, ['data' => $data]);
    }

    public function truecost()
    {
        $period = $this->resolvePeriod();
        $data = (new TrueTotalCostService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('truecost', $period, ['data' => $data]);
    }

    public function product()
    {
        $period = $this->resolvePeriod();
        $data = (new ProductAbcService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('product', $period, ['data' => $data]);
    }

    public function supplier()
    {
        $period = $this->resolvePeriod();
        $data = (new SupplierScorecardService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('supplier', $period, ['data' => $data]);
    }

    public function workcap()
    {
        $period = $this->resolvePeriod();
        $data = (new WorkingCapitalService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('workcap', $period, ['data' => $data]);
    }

    public function scenarios()
    {
        $period = $this->resolvePeriod();
        $companyId = $this->companyId();
        $settings = (new BiSettingService())->getWithDefaults('scenario', $companyId);
        $data = (new CashScenarioService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $settings);

        return $this->render('scenarios', $period, ['data' => $data, 'scenarioSettings' => $settings]);
    }

    public function variance()
    {
        $period = $this->resolvePeriod();
        $data = (new BudgetVarianceService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId(), $this->costCenterId());

        return $this->render('variance', $period, ['data' => $data]);
    }

    public function pvm()
    {
        $period = $this->resolvePeriod();
        $data = (new PriceVolumeMixService())->calculate($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('pvm', $period, ['data' => $data]);
    }

    public function breakeven()
    {
        $period = $this->resolvePeriod();
        $companyId = $this->companyId();
        $data = (new BreakEvenService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());

        return $this->render('breakeven', $period, [
            'data' => $data,
            'expenseAccounts' => Account::where('company_id', $companyId)->where('type', 'expense')->orderBy('name')->get(['id', 'code', 'name']),
            'expenseClassSaved' => BiExpenseClass::where('company_id', $companyId)->pluck('class', 'account_id'),
        ]);
    }

    public function cohorts()
    {
        $period = $this->resolvePeriod();
        $companyId = $this->companyId();
        $window = (int) (new BiSettingService())->get('assumptions', 'retention_window_days', $companyId, config('bi.defaults.assumptions.retention_window_days', 90));
        $data = (new CohortRetentionService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $window);

        return $this->render('cohorts', $period, ['data' => $data]);
    }

    public function board()
    {
        $period = $this->resolvePeriod();
        $data = (new BoardPackService())->compile($this->companyId(), $period['from'], $period['to'], $this->branchId());

        return $this->render('board', $period, ['data' => $data]);
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

        $filename = 'bi-' . str_replace('_', '-', $page) . '-' . $period['from'] . '-to-' . $period['to'] . '.csv';

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

        $period = $this->resolvePeriod();
        $data = $this->pageData($page, $this->companyId(), $period);

        return view('bi.print', [
            'page' => $page,
            'pageLabel' => self::PAGES[$page],
            'period' => $period,
            'data' => $data,
            'cur' => $this->currency(),
            'companyName' => $this->companyName(),
        ]);
    }

    public function saveScenarios()
    {
        $companyId = $this->companyId();
        $userId = (int) auth()->id();

        $values = [];
        foreach (['bull', 'base', 'bear'] as $key) {
            $values[$key] = [
                'inflow' => (float) request("scenario.{$key}.inflow", 1.0),
                'outflow' => (float) request("scenario.{$key}.outflow", 1.0),
            ];
        }

        (new BiSettingService())->setMany('scenario', $values, $companyId, $userId);

        return redirect()->back()->with('success', __('Scenario multipliers saved.'));
    }

    public function saveAllocation()
    {
        $companyId = $this->companyId();
        $userId = (int) auth()->id();

        $saved = (new BiSettingService())->getWithDefaults('allocation', $companyId);
        $pools = $saved['pools'] ?? [];

        if (!empty($pools)) {
            $driverByKey = [
                'payroll' => (string) request('allocation.payroll_driver', 'revenue_share'),
                'occupancy' => (string) request('allocation.occupancy_driver', 'floor_area'),
                'g_and_a' => (string) request('allocation.g_and_a_driver', 'revenue_share'),
            ];
            foreach ($pools as $key => &$pool) {
                if (isset($driverByKey[$key]) && in_array($driverByKey[$key], $pool['drivers'], true)) {
                    $pool['driver'] = $driverByKey[$key];
                }
            }
            unset($pool);
            (new BiSettingService())->set('allocation', 'pools', $pools, $companyId, $userId);
        }

        $floorArea = (bool) request('allocation.has_floor_area') ? 1 : 0;
        (new BiSettingService())->set('allocation', 'use_floor_area_sqm', $floorArea, $companyId, $userId);

        return redirect()->back()->with('success', __('Allocation drivers saved.'));
    }

    public function saveExpenseClass()
    {
        $companyId = $this->companyId();
        $userId = (int) auth()->id();

        $classifications = (array) request('classifications', []);
        foreach ($classifications as $accountId => $class) {
            if (!in_array($class, [BiExpenseClass::CLASS_FIXED, BiExpenseClass::CLASS_VARIABLE], true)) {
                continue;
            }
            BiExpenseClass::updateOrCreate(
                ['company_id' => $companyId, 'account_id' => (int) $accountId],
                ['class' => $class, 'updated_by' => $userId, 'updated_at' => now()]
            );
        }

        \App\Models\BiAuditLog::log($companyId, 'class.updated', 'expense_classes', null, null, $classifications, $userId);

        return redirect()->back()->with('success', __('Expense classifications saved.'));
    }

    public function saveAction()
    {
        $companyId = $this->companyId();

        $data = request()->validate([
            'description' => ['required', 'string', 'max:500'],
            'owner' => ['nullable', 'string', 'max:120'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $action = BiAction::create([
            'company_id' => $companyId,
            'page' => 'board',
            'description' => $data['description'],
            'owner' => $data['owner'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'open',
            'position' => $data['position'] ?? 0,
            'created_by' => (int) auth()->id(),
        ]);

        \App\Models\BiAuditLog::log($companyId, 'action.saved', 'actions', (string) $action->id, null, $action->description, (int) auth()->id());

        return redirect()->back()->with('success', __('Action added to the board pack.'));
    }

    private function render(string $page, array $period, array $extra = [])
    {
        $companyId = $this->companyId();

        return view('bi.' . $page, array_merge($extra, [
            'period' => $period,
            'pages' => self::PAGES,
            'activePage' => $page,
            'cur' => $this->currency(),
            'branches' => Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::forCompany($companyId)->active()->orderBy('name')->get(),
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

    private function companyName(): ?string
    {
        $company = \App\Models\Company::find(session('current_company_id'));
        return $company?->name;
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
        return $this->buildData($page, $companyId, $period);
    }

    private function buildData(string $page, int $companyId, array $period): array
    {
        switch ($page) {
            case 'overview':
                return (new OverviewInsightService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $this->costCenterId());
            case 'branch':
                return (new BranchProfitabilityService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'clv':
                return (new CustomerLifetimeValueService())->calculate($companyId, $this->branchId());
            case 'employee':
                return (new EmployeeProductivityService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'truecost':
                return (new TrueTotalCostService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'product':
                return (new ProductAbcService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'supplier':
                return (new SupplierScorecardService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'workcap':
                return (new WorkingCapitalService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'scenarios':
                $settings = (new BiSettingService())->getWithDefaults('scenario', $companyId);
                return (new CashScenarioService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $settings);
            case 'variance':
                return (new BudgetVarianceService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $this->costCenterId());
            case 'pvm':
                return (new PriceVolumeMixService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'breakeven':
                return (new BreakEvenService())->calculate($companyId, $period['from'], $period['to'], $this->branchId());
            case 'cohorts':
                $window = (int) (new BiSettingService())->get('assumptions', 'retention_window_days', $companyId, config('bi.defaults.assumptions.retention_window_days', 90));
                return (new CohortRetentionService())->calculate($companyId, $period['from'], $period['to'], $this->branchId(), $window);
            case 'board':
                return (new BoardPackService())->compile($companyId, $period['from'], $period['to'], $this->branchId());
        }

        return [];
    }

    private function exportRows(string $page, int $companyId, array $period): array
    {
        $data = $this->buildData($page, $companyId, $period);
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
            case 'branch':
                foreach (($data['branches'] ?? []) as $row) {
                    $add('Branch ' . ($row['branch_name'] ?? '?') . ' revenue', $row['revenue'] ?? 0);
                    $add('Branch ' . ($row['branch_name'] ?? '?') . ' net income', $row['net_income'] ?? 0);
                }
                break;
            case 'clv':
                $add('Total customers', $data['total_customers'] ?? 0);
                $add('Total net revenue', $data['total_revenue'] ?? 0);
                break;
            case 'employee':
                $add('Headcount', $data['headcount'] ?? 0);
                $add('Utilisation %', $data['utilisation_pct'] ?? 0);
                break;
            case 'truecost':
                $add('Grand total', $data['grand_total'] ?? 0);
                foreach (($data['branches'] ?? []) as $row) {
                    $add('Branch ' . ($row['branch_name'] ?? '?') . ' total', $row['total'] ?? 0);
                }
                break;
            case 'product':
                $add('Total revenue', $data['total_revenue'] ?? 0);
                $add('Class A value', $data['a_value'] ?? 0);
                break;
            case 'supplier':
                $add('Total spend', $data['total_spend'] ?? 0);
                $add('Total open PO', $data['total_open'] ?? 0);
                break;
            case 'workcap':
                $add('Working capital', $data['working_capital'] ?? 0);
                $add('Cash conversion cycle', $data['cash_conversion_cycle'] ?? 0);
                break;
            case 'scenarios':
                $add('Opening cash', $data['opening_cash'] ?? 0);
                foreach (($data['scenarios'] ?? []) as $row) {
                    $add($row['label'] . ' final balance', $row['final_balance'] ?? 0);
                }
                break;
            case 'variance':
                $add('Budgeted', $data['budget_total'] ?? 0);
                $add('Actual', $data['actual_total'] ?? 0);
                $add('Variance', $data['variance_total'] ?? 0);
                break;
            case 'pvm':
                $add('Current revenue', $data['current_revenue'] ?? 0);
                $add('Price effect', $data['price_effect'] ?? 0);
                $add('Volume effect', $data['volume_effect'] ?? 0);
                $add('Mix effect', $data['mix_effect'] ?? 0);
                break;
            case 'breakeven':
                $add('Break-even revenue', $data['break_even_revenue'] ?? 0);
                $add('Break-even units', $data['break_even_units'] ?? 0);
                break;
            case 'cohorts':
                $add('Total customers', $data['total_customers'] ?? 0);
                $add('Churn rate %', $data['churn_rate_pct'] ?? 0);
                break;
            case 'board':
                $add('Outstanding actions', $data['outstanding_actions'] ?? 0);
                foreach (($data['kpis'] ?? []) as $key => $kpi) {
                    $add(ucwords(str_replace('_', ' ', $key)), $kpi['value'] ?? 0);
                }
                break;
        }

        return $rows;
    }
}