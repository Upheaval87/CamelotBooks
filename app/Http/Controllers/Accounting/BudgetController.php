<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetAdjustment;
use App\Models\BudgetAlert;
use App\Models\BudgetAlertRule;
use App\Models\BudgetAuditLog;
use App\Models\BudgetLine;
use App\Models\BudgetTemplate;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\FiscalYear;
use App\Models\SystemSetting;
use App\Services\Accounting\ActualsService;
use App\Services\Accounting\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService = new BudgetService(),
        private ActualsService $actualsService = new ActualsService(),
    ) {}

    // ── §15 Dashboard ─────────────────────────────────────────

    public function dashboard(): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $fy = FiscalYear::where('company_id', $companyId)->where('status', 'open')->first();

        $kpis = $fy
            ? $this->actualsService->dashboardKpis($companyId, $fy->id)
            : ['total_budgets' => 0, 'approved_budgets' => 0, 'draft_budgets' => 0, 'pending_budgets' => 0,
               'total_income' => 0, 'total_budgeted' => 0, 'total_actual' => 0, 'total_remaining' => 0,
               'utilization' => 0, 'over_budget_count' => 0];

        $recentBudgets = Budget::where('company_id', $companyId)
            ->with('fiscalYear')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $recentAlerts = BudgetAlert::where('company_id', $companyId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $budgetsByStatus = Budget::where('company_id', $companyId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('accounting.budgets.dashboard', compact('kpis', 'recentBudgets', 'recentAlerts', 'budgetsByStatus', 'fy'));
    }

    // ── §11 Index ─────────────────────────────────────────────

    public function index(Request $request): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $fiscalYearId = $request->query('fiscal_year_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Budget::where('company_id', $companyId)
            ->with('fiscalYear', 'preparedByUser', 'approvedByUser');

        if ($fiscalYearId) {
            $query->where('fiscal_year_id', $fiscalYearId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $budgets = $query->orderByDesc('updated_at')->paginate(15)->appends($request->query());

        $stats = Budget::where('company_id', $companyId)
            ->selectRaw('status, count(*) as count, sum(total_income) as income, sum(total_expenses) as expenses')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $fiscalYears = FiscalYear::where('company_id', $companyId)->orderByDesc('start_date')->get();

        return view('accounting.budgets.index', compact('budgets', 'stats', 'fiscalYears'));
    }

    // ── §10 Create ────────────────────────────────────────────

    public function create(): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $fiscalYears = FiscalYear::where('company_id', $companyId)->where('status', 'open')->get();
        $accounts = Account::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->orderBy('name')->get();
        $templates = BudgetTemplate::where('company_id', $companyId)->get();
        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.budgets.create', compact('fiscalYears', 'accounts', 'branches', 'costCenters', 'templates', 'currencies'));
    }

    // ── §10 Store ─────────────────────────────────────────────

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'type'                => 'required|string|in:operating,capital,project,department,cash_flow',
            'fiscal_year_id'      => 'required|exists:fiscal_years,id',
            'period'              => 'required|string|in:annual,quarterly,monthly,custom',
            'department'          => 'nullable|string|max:255',
            'branch_id'           => 'nullable|exists:branches,id',
            'project'             => 'nullable|string|max:255',
            'cost_center_id'      => 'nullable|exists:cost_centers,id',
            'currency'            => 'nullable|string|max:10',
            'lines'               => 'required|array|min:1',
            'lines.*.line_type'   => 'required|string|in:income,expense',
            'lines.*.account_id'  => 'required|exists:accounts,id',
            'lines.*.annual_amount' => 'required|numeric|min:0',
            'lines.*.distribution' => 'nullable|string|in:even,seasonal,custom',
            'lines.*.distribution_config' => 'nullable|array',
            'lines.*.department'  => 'nullable|string',
            'lines.*.branch_id'   => 'nullable|exists:branches,id',
            'lines.*.project'     => 'nullable|string',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $validated['company_id'] = $companyId;

        $budget = $this->budgetService->create($validated, $userId);

        if ($request->input('action') === 'submit_for_approval') {
            $this->budgetService->submitForApproval($budget, $userId);
            return redirect()->route('accounting.budgets.show', $budget)
                ->with('success', 'Budget submitted for approval.');
        }

        if ($request->input('action') === 'save_and_new') {
            return redirect()->route('accounting.budgets.create')
                ->with('success', 'Budget saved as draft.');
        }

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget saved as draft.');
    }

    // ── §6 Show ───────────────────────────────────────────────

    public function show(Budget $budget): \Illuminate\View\View
    {
        $this->authorizeBudgetAccess($budget);

        $budget->load('lines.account', 'fiscalYear', 'preparedByUser', 'approvedByUser', 'adjustments');

        $actualsData = $this->actualsService->budgetVsActual($budget);

        // Build per-account actuals array for the actuals tab
        $actuals = [];
        foreach ($actualsData['lines'] ?? [] as $row) {
            $actuals[$row['account_id']] = $row['actual'];
        }

        $auditLogs = BudgetAuditLog::where('company_id', $budget->company_id)
            ->where('budget_id', $budget->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        $activeTab = request()->query('tab', 'overview');

        return view('accounting.budgets.show', compact('budget', 'actualsData', 'actuals', 'auditLogs', 'activeTab'));
    }

    // ── §10 Edit ──────────────────────────────────────────────

    public function edit(Budget $budget): \Illuminate\View\View
    {
        $this->authorizeBudgetAccess($budget);

        if (!$budget->isEditable()) {
            return redirect()->route('accounting.budgets.show', $budget)
                ->with('error', 'This budget cannot be edited in its current status.');
        }

        $companyId = session('current_company_id');
        $budget->load('lines.account');
        $fiscalYears = FiscalYear::where('company_id', $companyId)->get();
        $accounts = Account::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.budgets.edit', compact('budget', 'fiscalYears', 'accounts', 'branches', 'costCenters', 'currencies'));
    }

    // ── §10 Update ────────────────────────────────────────────

    public function update(Request $request, Budget $budget): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);
        $userId = auth()->id();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'type'                => 'required|string|in:operating,capital,project,department,cash_flow',
            'fiscal_year_id'      => 'required|exists:fiscal_years,id',
            'period'              => 'required|string|in:annual,quarterly,monthly,custom',
            'department'          => 'nullable|string|max:255',
            'branch_id'           => 'nullable|exists:branches,id',
            'project'             => 'nullable|string|max:255',
            'cost_center_id'      => 'nullable|exists:cost_centers,id',
            'currency'            => 'nullable|string|max:10',
            'lines'               => 'required|array|min:1',
            'lines.*.id'          => 'nullable|integer',
            'lines.*.line_type'   => 'required|string|in:income,expense',
            'lines.*.account_id'  => 'required|exists:accounts,id',
            'lines.*.annual_amount' => 'required|numeric|min:0',
            'lines.*.distribution' => 'nullable|string|in:even,seasonal,custom',
            'lines.*.distribution_config' => 'nullable|array',
            'lines.*.department'  => 'nullable|string',
            'lines.*.branch_id'   => 'nullable|exists:branches,id',
            'lines.*.project'     => 'nullable|string',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $budget = $this->budgetService->update($budget, $validated, $userId);

        if ($request->input('action') === 'submit_for_approval') {
            $this->budgetService->submitForApproval($budget, $userId);
            return redirect()->route('accounting.budgets.show', $budget)
                ->with('success', 'Budget submitted for approval.');
        }

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget updated.');
    }

    // ── §10 Submit / Approve / Reject / Lock / Unlock ─────────

    public function submit(Budget $budget): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);
        $this->budgetService->submitForApproval($budget, auth()->id());

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget submitted for approval.');
    }

    public function approve(Budget $budget, Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);
        $request->validate(['comment' => 'nullable|string|max:500']);

        $this->budgetService->approve($budget, auth()->id(), $request->input('comment'));

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget approved.');
    }

    public function reject(Budget $budget, Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);
        $request->validate(['reason' => 'required|string|max:500']);

        $this->budgetService->reject($budget, auth()->id(), $request->input('reason'));

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget rejected.');
    }

    public function lock(Budget $budget): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);
        $this->budgetService->lock($budget, auth()->id());

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget locked.');
    }

    public function unlock(Budget $budget): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeBudgetAccess($budget);
        $this->budgetService->unlock($budget, auth()->id());

        return redirect()->route('accounting.budgets.show', $budget)
            ->with('success', 'Budget unlocked.');
    }

    // ── §8 Budget vs Actual ───────────────────────────────────

    public function vsActual(Request $request): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $budgetId = $request->query('budget_id');
        $fiscalYearId = $request->query('fiscal_year_id');

        $budgets = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->with('fiscalYear')
            ->orderByDesc('updated_at')
            ->get();

        $selectedBudget = $budgetId
            ? $budgets->firstWhere('id', $budgetId)
            : $budgets->first();

        $reportData = null;
        if ($selectedBudget) {
            $actualsData = $this->actualsService->budgetVsActual($selectedBudget);
            $reportData = [
                'totalBudget' => $actualsData['total_budgeted'] ?? 0,
                'totalActual' => $actualsData['total_actual'] ?? 0,
                'totalVariance' => ($actualsData['total_budgeted'] ?? 0) - ($actualsData['total_actual'] ?? 0),
                'lines' => array_map(function ($line) use ($selectedBudget) {
                    $budgeted = $line['budgeted'] ?? 0;
                    $actual = $line['actual'] ?? 0;
                    $variance = $budgeted - $actual;
                    $variancePct = $budgeted > 0 ? round(abs($variance) / $budgeted * 100, 1) : 0;
                    $utilization = $budgeted > 0 ? round($actual / $budgeted * 100, 0) : 0;
                    $utilClass = $utilization <= 84 ? 'bg-u-ok' : ($utilization <= 99 ? 'bg-u-warn' : 'bg-u-bad');
                    return [
                        'account' => (object) ['code' => $line['account_code'] ?? '', 'name' => $line['account_name'] ?? ''],
                        'line' => (object) ['line_type' => $line['line_type'] ?? 'expense'],
                        'budget' => $budgeted,
                        'actual' => $actual,
                        'variance' => $variance,
                        'variancePct' => $variancePct,
                        'utilization' => $utilization,
                        'utilizationClass' => $utilClass,
                    ];
                }, $actualsData['lines'] ?? []),
            ];
        }

        $fiscalYears = FiscalYear::where('company_id', $companyId)->orderByDesc('start_date')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->orderBy('name')->get();
        $cs = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        return view('accounting.budgets.vsactual', compact('budgets', 'selectedBudget', 'reportData', 'fiscalYears', 'branches', 'costCenters', 'cs'));
    }

    // ── §7 Forecast ───────────────────────────────────────────

    public function forecast(Request $request): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $budgetId = $request->query('budget_id');
        $trendMonths = (int) $request->query('trend_months', 6);

        $budgets = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->with('fiscalYear')
            ->get();

        $selectedBudget = $budgetId
            ? $budgets->firstWhere('id', $budgetId)
            : $budgets->first();

        $forecastData = null;
        if ($selectedBudget) {
            $actualsData = $this->actualsService->budgetVsActual($selectedBudget);
            $totalActual = $actualsData['total_actual'] ?? 0;
            $avgMonthly = $trendMonths > 0 ? $totalActual / $trendMonths : 0;

            $forecastData = [
                'trendMonths' => $trendMonths,
                'avgMonthlyActual' => $avgMonthly,
                'forecastTotal' => $avgMonthly * 12,
                'total_budgeted' => $actualsData['total_budgeted'] ?? 0,
                'total_actual' => $totalActual,
                'lines' => array_map(function ($line) use ($trendMonths) {
                    $budgeted = $line['budgeted'] ?? 0;
                    $actual = $line['actual'] ?? 0;
                    $monthlyAvg = $trendMonths > 0 ? $actual / $trendMonths : 0;
                    $forecastAmount = $monthlyAvg * 12;
                    $variance = $budgeted - $forecastAmount;
                    return [
                        'account' => (object) ['code' => $line['account_code'] ?? '', 'name' => $line['account_name'] ?? ''],
                        'monthlyAvg' => $monthlyAvg,
                        'forecastAmount' => $forecastAmount,
                        'budget' => $budgeted,
                        'variance' => $variance,
                    ];
                }, $actualsData['lines'] ?? []),
            ];
        }

        return view('accounting.budgets.forecast', compact('budgets', 'selectedBudget', 'forecastData'));
    }

    // ── §7 Adjustments ────────────────────────────────────────

    public function adjustments(Request $request): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $budgetId = $request->query('budget_id');

        $query = BudgetAdjustment::where('company_id', $companyId)
            ->with('budget', 'requestedByUser', 'approvedByUser');

        if ($budgetId) {
            $query->where('budget_id', $budgetId);
        }

        $adjustments = $query->orderByDesc('created_at')->paginate(15)->appends($request->query());

        $budgets = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->get();

        $canApprove = auth()->user()->hasAnyRole(['system_admin', 'company_admin', 'accountant']);

        return view('accounting.budgets.adjustments', compact('adjustments', 'budgets', 'canApprove'));
    }

    public function storeAdjustment(Request $request): \Illuminate\Http\RedirectResponse
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'budget_id'      => 'required|exists:budgets,id',
            'budget_line_id' => 'nullable|exists:budget_lines,id',
            'type'           => 'required|string|in:increase,reduce,transfer',
            'from_line_id'   => 'nullable|exists:budget_lines,id',
            'to_line_id'     => 'nullable|exists:budget_lines,id',
            'amount'         => 'required|numeric|min:0.01',
            'reason'         => 'required|string|max:500',
            'original_amount' => 'nullable|numeric',
        ]);

        $validated['company_id'] = $companyId;
        $this->budgetService->createAdjustment($validated, auth()->id());

        return redirect()->route('accounting.budgets.adjustments', ['budget_id' => $validated['budget_id']])
            ->with('success', 'Adjustment request submitted.');
    }

    public function approveAdjustment(BudgetAdjustment $adjustment, Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['comment' => 'nullable|string|max:500']);
        $this->budgetService->approveAdjustment($adjustment, auth()->id(), $request->input('comment'));

        return redirect()->route('accounting.budgets.adjustments', ['budget_id' => $adjustment->budget_id])
            ->with('success', 'Adjustment approved and applied.');
    }

    public function rejectAdjustment(BudgetAdjustment $adjustment, Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->budgetService->rejectAdjustment($adjustment, auth()->id(), $request->input('reason'));

        return redirect()->route('accounting.budgets.adjustments', ['budget_id' => $adjustment->budget_id])
            ->with('success', 'Adjustment rejected.');
    }

    // ── §7 Alerts ─────────────────────────────────────────────

    public function alerts(): \Illuminate\View\View
    {
        $companyId = session('current_company_id');

        $alerts = BudgetAlert::where('company_id', $companyId)
            ->with('budget', 'rule')
            ->orderByDesc('created_at')
            ->paginate(20);

        $alertRules = BudgetAlertRule::where('company_id', $companyId)->get();
        $unreadCount = BudgetAlert::where('company_id', $companyId)->where('is_read', false)->count();
        $budgets = Budget::where('company_id', $companyId)->whereIn('status', ['approved', 'locked'])->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.budgets.alerts', compact('alerts', 'alertRules', 'unreadCount', 'budgets', 'branches'));
    }

    public function markAlertRead(BudgetAlert $alert): \Illuminate\Http\RedirectResponse
    {
        $alert->update(['is_read' => true]);
        return back()->with('success', 'Alert marked as read.');
    }

    public function storeAlertRule(Request $request): \Illuminate\Http\RedirectResponse
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'rule_type'            => 'required|string|in:threshold,unusual,low_balance',
            'warn_threshold'       => 'nullable|numeric|min:0|max:100',
            'exceed_threshold'     => 'nullable|numeric|min:0|max:100',
            'unusual_multiplier'   => 'nullable|numeric|min:1',
            'low_balance_threshold' => 'nullable|numeric|min:0|max:100',
            'scope'                => 'required|string|in:budget,department,line',
            'channels'             => 'nullable|array',
            'recipient_ids'        => 'nullable|array',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        BudgetAlertRule::create($validated);

        return redirect()->route('accounting.budgets.alerts')
            ->with('success', 'Alert rule created.');
    }

    // ── §12 Settings ──────────────────────────────────────────

    public function settings(): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $fy = FiscalYear::where('company_id', $companyId)->where('status', 'open')->first();

        $settings = \App\Models\SystemSetting::getMany('budgeting', $companyId);

        return view('accounting.budgets.settings', compact('fy', 'settings'));
    }

    // ── §13 Templates ─────────────────────────────────────────

    public function templates(): \Illuminate\View\View
    {
        $companyId = session('current_company_id');
        $templates = BudgetTemplate::where('company_id', $companyId)
            ->with('createdByUser', 'sourceBudget')
            ->orderByDesc('created_at')
            ->get();

        $budgets = Budget::where('company_id', $companyId)->orderByDesc('updated_at')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.budgets.templates', compact('templates', 'budgets', 'branches'));
    }

    public function storeTemplate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'source_budget_id'  => 'nullable|exists:budgets,id',
        ]);

        $validated['company_id'] = $companyId;
        $validated['created_by'] = auth()->id();

        $sourceBudget = null;
        if (!empty($validated['source_budget_id'])) {
            $sourceBudget = Budget::with('lines')->find($validated['source_budget_id']);
            $validated['template_data'] = [
                'lines' => $sourceBudget->lines->map(fn ($l) => [
                    'line_type' => $l->line_type,
                    'account_id' => $l->account_id,
                    'annual_amount' => $l->annual_amount,
                    'distribution' => $l->distribution,
                ])->toArray(),
            ];
            $validated['lines_count'] = $sourceBudget->lines->count();
        } else {
            $validated['template_data'] = ['lines' => []];
            $validated['lines_count'] = 0;
        }

        BudgetTemplate::create($validated);

        return redirect()->route('accounting.budgets.templates')
            ->with('success', 'Template created.');
    }

    // ── §14 Reports ───────────────────────────────────────────

    public function reports(Request $request): \Illuminate\View\View
    {
        $companyId = session('current_company_id');

        $fiscalYears = FiscalYear::where('company_id', $companyId)->orderByDesc('start_date')->get();
        $currentFiscalYear = FiscalYear::where('company_id', $companyId)->where('status', 'open')->first()
            ?? $fiscalYears->first();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $cs = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        $reportType = $request->query('report_type', 'vs_actual');
        $fiscalYearId = $request->query('fiscal_year_id', $currentFiscalYear?->id);
        $period = $request->query('period', 'annual');
        $department = $request->query('department');
        $branchId = $request->query('branch_id');

        $fiscalYear = $fiscalYears->firstWhere('id', $fiscalYearId);

        // Determine date range from period + fiscal year
        [$dateFrom, $dateTo] = $this->resolvePeriodRange($fiscalYear, $period);

        // Get active budgets for the selected fiscal year
        $budgets = Budget::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->when($fiscalYearId, fn($q) => $q->where('fiscal_year_id', $fiscalYearId))
            ->when($department, fn($q) => $q->where('department', $department))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Build per-line report data
        $lines = [];
        $totalBudgeted = 0;
        $totalActual = 0;
        $warnings = 0;
        $overCount = 0;
        $monthlyBudget = array_fill(0, 12, 0);
        $monthlyActual = array_fill(0, 12, 0);
        $monthLabels = [];

        foreach ($budgets as $budget) {
            $budgetLines = $budget->lines()->with('account')->get();
            foreach ($budgetLines as $bl) {
                $budgeted = (float) $bl->annual_amount;
                $actual = $this->actualsService->annualActual($bl, $dateFrom, $dateTo);
                $variance = $budgeted - $actual;
                $variancePct = $budgeted > 0 ? round(abs($variance) / $budgeted * 100, 1) : 0;
                $utilization = $budgeted > 0 ? round(($actual / $budgeted) * 100, 0) : 0;

                // Income: shortfall = unfavourable; expense: underspend = favourable
                if ($bl->line_type === 'income') {
                    $isFavourable = $variance <= 0; // actual >= budget is good for income
                    $statusClass = $utilization >= 90 ? 'ok' : ($utilization >= 80 ? 'warn' : 'crit');
                } else {
                    $isFavourable = $variance >= 0; // underspend is good for expense
                    $statusClass = $utilization <= 84 ? 'ok' : ($utilization <= 94 ? 'warn' : 'crit');
                }
                $statusLabel = $statusClass === 'ok' ? 'On track' : ($statusClass === 'warn' ? 'Warning' : 'Over');

                if ($statusClass === 'warn') $warnings++;
                if ($statusClass === 'crit') $overCount++;

                $totalBudgeted += $budgeted;
                $totalActual += $actual;

                // Monthly chart data
                $monthlyLine = $this->actualsService->monthlyActuals($bl, $dateFrom, $dateTo);
                $budgetMonthly = $bl->monthlyBreakdown();
                for ($i = 0; $i < 12; $i++) {
                    $monthlyBudget[$i] += $budgetMonthly[$i];
                    $monthlyActual[$i] += $monthlyLine[$i];
                }

                $lines[] = [
                    'account_code'  => $bl->account?->code ?? '',
                    'account_name'  => $bl->account?->name ?? '',
                    'line_type'     => $bl->line_type,
                    'budgeted'      => $budgeted,
                    'actual'        => $actual,
                    'variance'      => $variance,
                    'variancePct'   => $variancePct,
                    'utilization'   => $utilization,
                    'statusClass'   => $statusClass,
                    'statusLabel'   => $statusLabel,
                    'budget_id'     => $budget->id,
                ];
            }
        }

        // Sort by line_type then by absolute variance descending for variance report
        if ($reportType === 'variance') {
            usort($lines, fn($a, $b) => abs($b['variance']) <=> abs($a['variance']));
        } elseif ($reportType === 'utilization') {
            usort($lines, fn($a, $b) => $b['utilization'] <=> $a['utilization']);
        }

        // Month labels for the chart (only up to current month within the range)
        $startMonth = $dateFrom ? Carbon::parse($dateFrom) : now()->startOfYear();
        for ($i = 0; $i < 12; $i++) {
            $m = $startMonth->copy()->addMonths($i);
            $monthLabels[] = $m->format('M');
            // Zero out months outside the date range
            if ($dateTo && $m->startOfMonth()->toDateString() > $dateTo) {
                $monthlyBudget[$i] = 0;
                $monthlyActual[$i] = 0;
            }
        }

        // Filter out zero-only months from chart
        $chartData = ['labels' => [], 'budget' => [], 'actual' => []];
        for ($i = 0; $i < 12; $i++) {
            if ($monthlyBudget[$i] > 0 || $monthlyActual[$i] > 0 || $i < now()->month) {
                $chartData['labels'][] = $monthLabels[$i] ?? '';
                $chartData['budget'][] = $monthlyBudget[$i];
                $chartData['actual'][] = $monthlyActual[$i];
            }
        }

        $totalVariance = $totalBudgeted - $totalActual;
        $totalVariancePct = $totalBudgeted > 0 ? round(abs($totalVariance) / $totalBudgeted * 100, 1) : 0;
        $overallUtil = $totalBudgeted > 0 ? round(($totalActual / $totalBudgeted) * 100, 0) : 0;

        $reportData = [
            'lines'             => $lines,
            'totalBudgeted'     => $totalBudgeted,
            'totalActual'       => $totalActual,
            'totalVariance'     => $totalVariance,
            'totalVariancePct'  => $totalVariancePct,
            'overallUtil'       => $overallUtil,
            'warnings'          => $warnings,
            'overCount'         => $overCount,
            'chartData'         => $chartData,
        ];

        // Department options (unique from budgets)
        $departments = Budget::where('company_id', $companyId)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->pluck('department')
            ->unique()
            ->sort()
            ->values();

        // CSV export
        if ($request->query('export') === 'csv') {
            return $this->exportReportCsv($reportData, $reportType, $fiscalYear);
        }

        return view('accounting.budgets.reports', compact(
            'fiscalYears', 'currentFiscalYear', 'branches', 'departments',
            'reportType', 'period', 'department', 'branchId', 'fiscalYear',
            'reportData', 'cs'
        ));
    }

    private function resolvePeriodRange(?FiscalYear $fy, string $period): array
    {
        if (!$fy) {
            return [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()];
        }

        $fyStart = Carbon::parse($fy->start_date);
        $fyEnd = Carbon::parse($fy->end_date);

        return match ($period) {
            'q1' => [$fyStart->copy()->startOfMonth()->toDateString(), $fyStart->copy()->addMonths(2)->endOfMonth()->toDateString()],
            'q2' => [$fyStart->copy()->addMonths(3)->startOfMonth()->toDateString(), $fyStart->copy()->addMonths(5)->endOfMonth()->toDateString()],
            'q3' => [$fyStart->copy()->addMonths(6)->startOfMonth()->toDateString(), $fyStart->copy()->addMonths(8)->endOfMonth()->toDateString()],
            'q4' => [$fyStart->copy()->addMonths(9)->startOfMonth()->toDateString(), $fyStart->copy()->addMonths(11)->endOfMonth()->toDateString()],
            'mtd' => [$fyStart->copy()->startOfMonth()->toDateString(), now()->toDateString()],
            default => [$fyStart->toDateString(), $fyEnd->toDateString()],
        };
    }

    private function exportReportCsv(array $reportData, string $reportType, ?FiscalYear $fiscalYear): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'budget-report-' . $reportType . '-' . now()->format('Y-m-d-His') . '.csv';
        $headers = ['Account Code', 'Account Name', 'Type', 'Budget', 'Actual', 'Variance', 'Var %', 'Utilization %', 'Status'];

        return response()->streamDownload(function () use ($reportData, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($reportData['lines'] as $line) {
                fputcsv($handle, [
                    $line['account_code'],
                    $line['account_name'],
                    $line['line_type'],
                    number_format($line['budgeted'], 2),
                    number_format($line['actual'], 2),
                    number_format($line['variance'], 2),
                    $line['variancePct'] . '%',
                    $line['utilization'] . '%',
                    $line['statusLabel'],
                ]);
            }

            // Totals row
            fputcsv($handle, [
                '', 'TOTAL', '',
                number_format($reportData['totalBudgeted'], 2),
                number_format($reportData['totalActual'], 2),
                number_format($reportData['totalVariance'], 2),
                $reportData['totalVariancePct'] . '%',
                $reportData['overallUtil'] . '%',
                '',
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ── Helper ────────────────────────────────────────────────

    private function authorizeBudgetAccess(Budget $budget): void
    {
        $companyId = session('current_company_id');
        if ($budget->company_id !== $companyId) {
            abort(403, 'Unauthorized access to this budget.');
        }
    }
}
