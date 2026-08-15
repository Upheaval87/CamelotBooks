<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Models\ExpenseRecurringTemplate;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\Vendor;
use App\Services\Accounting\BudgetCheckService;
use App\Services\Accounting\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected BudgetCheckService $budgetCheckService,
    ) {
    }

    protected function companyId(): int
    {
        return (int) session('current_company_id');
    }

    protected function cs(): string
    {
        return (string) SystemSetting::getValue('currency', 'currency_symbol', $this->companyId(), '$');
    }

    protected function expenseScopes(Request $request)
    {
        $query = Expense::with(['category', 'vendor', 'employee'])
            ->where('company_id', $this->companyId());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        if ($request->filled('q')) {
            $needle = '%' . $request->q . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('expense_number', 'like', $needle)
                    ->orWhere('reference', 'like', $needle)
                    ->orWhere('memo', 'like', $needle)
                    ->orWhere('department', 'like', $needle)
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', $needle))
                    ->orWhereHas('employee', fn ($e) => $e->where('first_name', 'like', $needle)->orWhere('last_name', 'like', $needle));
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $this->requirePermission('expenses.view');

        $companyId = $this->companyId();

        $expenses = $this->expenseScopes($request)
            ->orderByDesc('expense_date')
            ->paginate(15)
            ->withQueryString();

        $counts = Expense::where('company_id', $companyId)
            ->selectRaw("status, count(*) as c, coalesce(sum(amount), 0) as total")
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [
            'all' => (int) $counts->sum('c'),
            'pending' => (int) ($counts['pending']->c ?? 0),
            'approved' => (int) ($counts['approved']->c ?? 0),
            'posted' => (int) ($counts['posted']->c ?? 0),
            'paid' => (int) ($counts['paid']->c ?? 0),
            'rejected' => (int) ($counts['rejected']->c ?? 0),
            'draft' => (int) ($counts['draft']->c ?? 0),
            'total_amount' => (float) $counts->sum('total'),
        ];

        $categories = ExpenseCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.expenses.index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => $categories,
            'cs' => $this->cs(),
            'filters' => [
                'status' => $request->status,
                'category_id' => $request->category_id,
                'department' => $request->department,
                'payment_status' => $request->payment_status,
                'q' => $request->q,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],
        ]);
    }

    public function dashboard(Request $request)
    {
        $this->requirePermission('expenses.view');

        $companyId = $this->companyId();

        $yearStart = now()->startOfYear();
        $monthStart = now()->startOfMonth();

        $activeBase = ['draft', 'pending', 'approved', 'posted', 'paid'];

        $totalYtd = (float) Expense::where('company_id', $companyId)
            ->whereIn('status', $activeBase)
            ->where('expense_date', '>=', $yearStart)
            ->sum('amount');

        $monthTotal = (float) Expense::where('company_id', $companyId)
            ->whereIn('status', $activeBase)
            ->whereBetween('expense_date', [$monthStart, now()])
            ->sum('amount');

        $pendingTotal = (float) Expense::where('company_id', $companyId)
            ->where('status', Expense::STATUS_PENDING)
            ->sum('amount');

        $pendingCount = (int) Expense::where('company_id', $companyId)
            ->where('status', Expense::STATUS_PENDING)
            ->count();

        $unposted = (float) Expense::where('company_id', $companyId)
            ->where('status', Expense::STATUS_APPROVED)
            ->sum('amount');

        $unpostedCount = (int) Expense::where('company_id', $companyId)
            ->where('status', Expense::STATUS_APPROVED)
            ->count();

        $byCategory = Expense::where('company_id', $companyId)
            ->whereIn('status', $activeBase)
            ->whereBetween('expense_date', [$monthStart, now()])
            ->whereNotNull('category_id')
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $trendRows = Expense::where('company_id', $companyId)
            ->whereIn('status', $activeBase)
            ->where('expense_date', '>=', now()->subMonths(7)->startOfMonth())
            ->selectRaw('expense_date, amount')
            ->get()
            ->groupBy(fn ($row) => $row->expense_date?->format('Y-m'));

        $trend = collect();
        for ($i = 7; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $key = $m->format('Y-m');
            $row = $trendRows->get($key);
            $trend->push([
                'label' => $m->format('M'),
                'total' => (float) ($row ? $row->sum('amount') : 0),
            ]);
        }

        $trendMax = max((float) $trend->max('total'), 0.01);

        $pendingExpenses = Expense::with(['category', 'vendor'])
            ->where('company_id', $companyId)
            ->where('status', Expense::STATUS_PENDING)
            ->orderByDesc('expense_date')
            ->limit(5)
            ->get();

        $pendingClaims = ExpenseClaim::with(['employee'])
            ->where('company_id', $companyId)
            ->where('status', ExpenseClaim::STATUS_PENDING)
            ->orderByDesc('expense_date')
            ->limit(5)
            ->get();

        $expenses = $this->expenseScopes($request)
            ->orderByDesc('expense_date')
            ->paginate(15)
            ->withQueryString();

        $counts = Expense::where('company_id', $companyId)
            ->selectRaw("status, count(*) as c, coalesce(sum(amount), 0) as total")
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [
            'all' => (int) $counts->sum('c'),
            'pending' => (int) ($counts['pending']->c ?? 0),
            'approved' => (int) ($counts['approved']->c ?? 0),
            'posted' => (int) ($counts['posted']->c ?? 0),
            'paid' => (int) ($counts['paid']->c ?? 0),
            'rejected' => (int) ($counts['rejected']->c ?? 0),
            'draft' => (int) ($counts['draft']->c ?? 0),
            'total_amount' => (float) $counts->sum('total'),
        ];

        $categories = ExpenseCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.expenses.dashboard', [
            'totalYtd' => $totalYtd,
            'monthTotal' => $monthTotal,
            'monthLabel' => now()->format('M Y'),
            'pendingTotal' => $pendingTotal,
            'pendingCount' => $pendingCount,
            'unposted' => $unposted,
            'unpostedCount' => $unpostedCount,
            'byCategory' => $byCategory,
            'trend' => $trend,
            'trendMax' => $trendMax,
            'pendingExpenses' => $pendingExpenses,
            'pendingClaims' => $pendingClaims,
            'expenses' => $expenses,
            'stats' => $stats,
            'categories' => $categories,
            'cs' => $this->cs(),
        ]);
    }

    public function create(Request $request)
    {
        $this->requirePermission('expenses.create');

        $companyId = $this->companyId();
        $selectedVendorId = $request->input('vendor_id');

        return $this->formData(compact('selectedVendorId'), 'create');
    }

    public function edit(Expense $expense)
    {
        $this->requirePermission('expenses.edit');
        $this->authorizeScope($expense);

        if (!$expense->isEditable()) {
            abort(403, 'Only draft or returned expenses can be edited.');
        }

        $expense->load(['lines.product', 'lines.expenseAccount', 'lines.costCenter', 'vendor', 'category', 'employee']);

        return $this->formData(['expense' => $expense, 'budget' => $this->budgetFor($expense)], 'edit');
    }

    protected function formData(array $extra = [], string $mode = 'create')
    {
        $companyId = $this->companyId();

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->whereIn('type', ['expense', 'asset'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('type', 'asset')
            ->where('sub_type', 'current_asset')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $categories = ExpenseCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = Currency::query()->active()->ordered()->get();

        $departments = Expense::where('company_id', $companyId)
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->all();

        return view('accounting.expenses.' . ($mode === 'edit' ? 'edit' : 'create'), array_merge([
            'vendors' => $vendors,
            'products' => $products,
            'expenseAccounts' => $expenseAccounts,
            'bankAccounts' => $bankAccounts,
            'costCenters' => $costCenters,
            'categories' => $categories,
            'employees' => $employees,
            'branches' => $branches,
            'currencies' => $currencies,
            'departments' => $departments,
            'cs' => $this->cs(),
        ], $extra));
    }

    protected function validateExpenseInput(Request $request)
    {
        return $request->validate([
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'department' => ['nullable', 'string', 'max:120'],
            'expense_date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'payment_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'payment_date' => ['nullable', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'lines.*.department' => ['nullable', 'string', 'max:120'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv'],
            'delete_documents' => ['nullable', 'array'],
            'delete_documents.*' => ['integer'],
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('expenses.create');

        $validated = $this->validateExpenseInput($request);
        $validated['company_id'] = $this->companyId();

        try {
            $expense = $this->expenseService->create($validated, auth()->id());

            $this->handleAttachments($request, $expense);

            if ($request->input('action') === 'submit') {
                $expense = $this->expenseService->submit($expense, auth()->id());
            }

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense created successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, Expense $expense)
    {
        $this->requirePermission('expenses.edit');
        $this->authorizeScope($expense);

        if (!$expense->isEditable()) {
            abort(403, 'Only draft or returned expenses can be updated.');
        }

        $validated = $this->validateExpenseInput($request);

        try {
            $expense = $this->expenseService->update($expense, $validated, auth()->id());

            $this->handleAttachments($request, $expense);

            if ($request->input('action') === 'submit') {
                $expense = $this->expenseService->submit($expense, auth()->id());
            }

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense updated successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Expense $expense)
    {
        $this->requirePermission('expenses.view');
        $this->authorizeScope($expense);

        $expense->load([
            'vendor', 'bankAccount', 'paymentAccount', 'category', 'employee', 'branch', 'costCenter',
            'lines.product', 'lines.costCenter', 'lines.expenseAccount',
            'journalEntry', 'payments.account', 'payments.journalEntry', 'payments.paidByUser',
            'attachments', 'claim',
            'createdByUser', 'submittedByUser', 'approvedByUser', 'rejectedByUser', 'returnedByUser',
            'postedByUser', 'voidedByUser', 'budgetApproverUser',
        ]);

        $auditTrail = \App\Models\AccountAuditLog::where('journalable_type', Expense::class)
            ->where('journalable_id', $expense->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $workflow = $this->workflowSteps($expense);

        $budget = $this->budgetFor($expense);

        return view('accounting.expenses.show', [
            'expense' => $expense,
            'cs' => $this->cs(),
            'auditTrail' => $auditTrail,
            'workflow' => $workflow,
            'budget' => $budget,
        ]);
    }

    protected function workflowSteps(Expense $expense): array
    {
        $steps = [
            ['key' => 'created', 'title' => 'Expense Created', 'meta' => $expense->createdByUser?->name ?? '—', 'when' => $expense->created_at, 'state' => 'done'],
        ];

        $state = $expense->isVoid() ? 'void'
            : ($expense->isPosted() || $expense->isPaid() ? 'posted'
                : ($expense->isApproved() ? 'approved'
                    : ($expense->isRejected() ? 'rejected'
                        : ($expense->isReturned() ? 'returned' : 'pending'))));

        $steps[] = [
            'key' => 'dept_manager',
            'title' => 'Department Manager',
            'meta' => $expense->approved_by ? ($expense->approvedByUser?->name ?? 'Approved') : 'Awaiting approval',
            'when' => $expense->approved_at,
            'state' => in_array($state, ['approved', 'posted']) ? 'done' : ($state === 'pending' ? 'cur' : 'todo'),
        ];

        $steps[] = [
            'key' => 'finance_manager',
            'title' => 'Finance Manager',
            'meta' => $expense->approved_by ? ($expense->approvedByUser?->name ?? 'Approved') : 'Awaiting approval',
            'when' => $expense->approved_at,
            'state' => in_array($state, ['approved', 'posted']) ? 'done' : ($state === 'pending' ? 'cur' : 'todo'),
        ];

        $postedMeta = 'Not yet posted';
        if ($expense->journalEntry) {
            $postedMeta = 'JV-' . $expense->journalEntry->id . ' · ' . ($expense->postedByUser?->name ?? 'Posted');
        } elseif ($expense->isPosted() || $expense->isPaid()) {
            $postedMeta = 'Posted · ' . ($expense->postedByUser?->name ?? 'System');
        }

        $steps[] = [
            'key' => 'posted',
            'title' => $expense->isVoid() ? 'Voided' : 'Posted',
            'meta' => $expense->isVoid() ? ($expense->void_reason ?? 'Voided') : $postedMeta,
            'when' => $expense->posted_at ?? $expense->voided_at,
            'state' => ($expense->isPosted() || $expense->isPaid()) ? 'done' : ($expense->isVoid() ? 'todo' : 'todo'),
        ];

        return $steps;
    }

    protected function budgetFor(Expense $expense): ?array
    {
        if ($expense->lines->isEmpty()) {
            return null;
        }

        try {
            $budget = $this->budgetCheckService->check(
                $expense->company_id,
                $expense->lines->map(fn ($l) => [
                    'expense_account_id' => $l->expense_account_id,
                    'estimated_total' => (float) $l->line_total,
                ])->all(),
                $expense->expense_date->format('Y-m-d')
            );

            return [
                'status' => $budget['status'],
                'total_budgeted' => (float) $budget['total_budgeted'],
                'total_spent' => (float) $budget['total_spent'],
                'total_requested' => (float) $budget['total_requested'],
                'total_available' => (float) $budget['total_available'],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function submit(Expense $expense)
    {
        $this->requirePermission('expenses.submit');
        $this->authorizeScope($expense);

        try {
            $expense = $this->expenseService->submit($expense, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense submitted for approval.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function approve(Expense $expense)
    {
        $this->requirePermission('expenses.approve');
        $this->authorizeScope($expense);

        try {
            $expense = $this->expenseService->approve($expense, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense approved.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, Expense $expense)
    {
        $this->requirePermission('expenses.reject');
        $this->authorizeScope($expense);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $expense = $this->expenseService->reject($expense, $validated['reason'], auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense rejected.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function returnForCorrection(Request $request, Expense $expense)
    {
        $this->requirePermission('expenses.return');
        $this->authorizeScope($expense);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $expense = $this->expenseService->returnForCorrection($expense, $validated['reason'], auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense returned for correction.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function authorizeBudget(Request $request, Expense $expense)
    {
        $this->requirePermission('expenses.post');
        $this->authorizeScope($expense);

        $validated = $request->validate([
            'budget_reason' => ['required', 'string', 'max:1000'],
            'budget_approver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $expense = $this->expenseService->authorizeBudget(
                $expense,
                $validated['budget_reason'],
                (int) $validated['budget_approver_id'],
                auth()->id()
            );

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Budget override authorized.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function post(Expense $expense)
    {
        $this->requirePermission('expenses.post');
        $this->authorizeScope($expense);

        try {
            $expense = $this->expenseService->post($expense, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense posted successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function recordPayment(Request $request, Expense $expense)
    {
        $this->requirePermission('expenses.pay');
        $this->authorizeScope($expense);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'payment_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $payment = $this->expenseService->recordPayment($expense, $validated, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', "Payment {$payment->payment_number} recorded.");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(Request $request, Expense $expense)
    {
        $this->requirePermission('expenses.void');
        $this->authorizeScope($expense);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->expenseService->void($expense, $validated['reason'], auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense voided successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function duplicate(Expense $expense)
    {
        $this->requirePermission('expenses.duplicate');
        $this->authorizeScope($expense);

        try {
            $copy = $this->expenseService->duplicate($expense, auth()->id());

            return redirect()->route('accounting.expenses.show', $copy)
                ->with('success', "Expense duplicated to {$copy->expense_number}.");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Expense $expense)
    {
        $this->requirePermission('expenses.delete');
        $this->authorizeScope($expense);

        try {
            $this->expenseService->destroy($expense, auth()->id());

            return redirect()->route('accounting.expenses.index')
                ->with('success', 'Expense deleted.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Expense Claims
    // ─────────────────────────────────────────────────────────────

    public function claimsIndex(Request $request)
    {
        $this->requirePermission('expense-claims.view');

        $query = ExpenseClaim::with(['employee'])
            ->where('company_id', $this->companyId());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $needle = '%' . $request->q . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('claim_number', 'like', $needle)
                    ->orWhere('description', 'like', $needle)
                    ->orWhereHas('employee', fn ($e) => $e->where('first_name', 'like', $needle)->orWhere('last_name', 'like', $needle));
            });
        }

        $claims = $query->orderByDesc('expense_date')->paginate(15)->withQueryString();

        $counts = ExpenseClaim::where('company_id', $this->companyId())
            ->selectRaw("status, count(*) as c, coalesce(sum(amount), 0) as total")
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [
            'all' => (int) $counts->sum('c'),
            'draft' => (int) ($counts['draft']->c ?? 0),
            'pending' => (int) ($counts['pending']->c ?? 0),
            'approved' => (int) ($counts['approved']->c ?? 0),
            'rejected' => (int) ($counts['rejected']->c ?? 0),
            'reimbursed' => (int) ($counts['reimbursed']->c ?? 0),
            'total_amount' => (float) $counts->sum('total'),
        ];

        return view('accounting.expenses.claims-index', [
            'claims' => $claims,
            'stats' => $stats,
            'cs' => $this->cs(),
            'filters' => [
                'status' => $request->status,
                'q' => $request->q,
            ],
        ]);
    }

    public function claimCreate()
    {
        $this->requirePermission('expense-claims.create');

        return view('accounting.expenses.claims-create', $this->claimFormData());
    }

    public function claimStore(Request $request)
    {
        $this->requirePermission('expense-claims.create');

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'reimburse_to' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'memo' => ['nullable', 'string', 'max:2000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv'],
        ]);

        $validated['company_id'] = $this->companyId();

        try {
            $claim = $this->expenseService->createClaim($validated, auth()->id());

            $this->handleClaimAttachments($request, $claim);

            if ($request->input('action') === 'submit') {
                $claim = $this->expenseService->submitClaim($claim, auth()->id());
            }

            return redirect()->route('accounting.expenses.claims.show', $claim)
                ->with('success', 'Expense claim created successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function claimShow(ExpenseClaim $claim)
    {
        $this->requirePermission('expense-claims.view');
        $this->authorizeClaimScope($claim);

        $claim->load(['employee', 'branch', 'costCenter', 'category', 'expense', 'attachments', 'submittedBy', 'approvedBy', 'rejectedBy', 'reimbursedBy']);

        $auditTrail = \App\Models\AccountAuditLog::where('journalable_type', ExpenseClaim::class)
            ->where('journalable_id', $claim->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('accounting.expenses.claims-show', [
            'claim' => $claim,
            'cs' => $this->cs(),
            'auditTrail' => $auditTrail,
        ]);
    }

    public function claimSubmit(ExpenseClaim $claim)
    {
        $this->requirePermission('expense-claims.submit');
        $this->authorizeClaimScope($claim);

        try {
            $claim = $this->expenseService->submitClaim($claim, auth()->id());

            return redirect()->route('accounting.expenses.claims.show', $claim)
                ->with('success', 'Claim submitted for approval.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function claimApprove(ExpenseClaim $claim)
    {
        $this->requirePermission('expense-claims.approve');
        $this->authorizeClaimScope($claim);

        try {
            $claim = $this->expenseService->approveClaim($claim, auth()->id());

            return redirect()->route('accounting.expenses.claims.show', $claim)
                ->with('success', 'Claim approved. A draft expense was created from it.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function claimReject(Request $request, ExpenseClaim $claim)
    {
        $this->requirePermission('expense-claims.reject');
        $this->authorizeClaimScope($claim);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $claim = $this->expenseService->rejectClaim($claim, $validated['reason'], auth()->id());

            return redirect()->route('accounting.expenses.claims.show', $claim)
                ->with('success', 'Claim rejected.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function claimReimburse(ExpenseClaim $claim)
    {
        $this->requirePermission('expense-claims.reimburse');
        $this->authorizeClaimScope($claim);

        try {
            $claim = $this->expenseService->reimburseClaim($claim, auth()->id());

            return redirect()->route('accounting.expenses.claims.show', $claim)
                ->with('success', 'Claim reimbursed.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function claimDestroy(ExpenseClaim $claim)
    {
        $this->requirePermission('expense-claims.delete');
        $this->authorizeClaimScope($claim);

        if (!$claim->isDraft()) {
            return redirect()->back()->withErrors(['error' => 'Only draft claims can be deleted.']);
        }

        DB::transaction(function () use ($claim) {
            $claim->attachments()->delete();
            $claim->delete();
        });

        return redirect()->route('accounting.expenses.claims.index')
            ->with('success', 'Claim deleted.');
    }

    protected function claimFormData(): array
    {
        $companyId = $this->companyId();

        return [
            'employees' => Employee::where('company_id', $companyId)->where('is_active', true)->orderBy('first_name')->get(),
            'branches' => Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get(),
            'categories' => ExpenseCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'currencies' => Currency::query()->active()->ordered()->get(),
            'cs' => $this->cs(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Recurring Templates
    // ─────────────────────────────────────────────────────────────

    public function recurringIndex()
    {
        $this->requirePermission('expense-recurring.view');

        $templates = ExpenseRecurringTemplate::with(['category', 'vendor', 'expenseAccount'])
            ->where('company_id', $this->companyId())
            ->orderBy('name')
            ->get();

        return view('accounting.expenses.recurring-index', [
            'templates' => $templates,
            'cs' => $this->cs(),
        ]);
    }

    public function recurringCreate()
    {
        $this->requirePermission('expense-recurring.create');

        return view('accounting.expenses.recurring-form', $this->recurringFormData());
    }

    public function recurringEdit(ExpenseRecurringTemplate $template)
    {
        $this->requirePermission('expense-recurring.edit');
        $this->authorizeRecurringScope($template);

        return view('accounting.expenses.recurring-form', array_merge(
            ['template' => $template],
            $this->recurringFormData()
        ));
    }

    public function recurringStore(Request $request)
    {
        $this->requirePermission('expense-recurring.create');

        $validated = $this->validateRecurringInput($request);
        $validated['company_id'] = $this->companyId();
        $validated['created_by'] = auth()->id();

        try {
            $template = ExpenseRecurringTemplate::create($validated);

            return redirect()->route('accounting.expenses.recurring.index')
                ->with('success', 'Recurring expense template created.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function recurringUpdate(Request $request, ExpenseRecurringTemplate $template)
    {
        $this->requirePermission('expense-recurring.edit');
        $this->authorizeRecurringScope($template);

        $validated = $this->validateRecurringInput($request);

        try {
            $template->update($validated);

            return redirect()->route('accounting.expenses.recurring.index')
                ->with('success', 'Recurring expense template updated.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function recurringToggle(ExpenseRecurringTemplate $template)
    {
        $this->requirePermission('expense-recurring.edit');
        $this->authorizeRecurringScope($template);

        $template->update(['is_active' => !$template->is_active]);

        return redirect()->route('accounting.expenses.recurring.index')
            ->with('success', $template->is_active ? 'Recurring template enabled.' : 'Recurring template paused.');
    }

    public function recurringDestroy(ExpenseRecurringTemplate $template)
    {
        $this->requirePermission('expense-recurring.delete');
        $this->authorizeRecurringScope($template);

        $template->delete();

        return redirect()->route('accounting.expenses.recurring.index')
            ->with('success', 'Recurring expense template deleted.');
    }

    protected function validateRecurringInput(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'frequency' => ['required', 'in:daily,weekly,monthly,quarterly,yearly'],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function recurringFormData(): array
    {
        $companyId = $this->companyId();

        return [
            'categories' => ExpenseCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'vendors' => Vendor::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get(),
            'expenseAccounts' => Account::where('company_id', $companyId)->whereIn('type', ['expense', 'asset'])->where('is_active', true)->orderBy('code')->get(),
            'currencies' => Currency::query()->active()->ordered()->get(),
            'frequencies' => ExpenseRecurringTemplate::FREQUENCY_LABELS,
            'cs' => $this->cs(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Categories
    // ─────────────────────────────────────────────────────────────

    public function categoriesIndex()
    {
        $this->requirePermission('expense-categories.view');

        $categories = ExpenseCategory::withCount(['expenses' => fn ($q) => $q->whereNot('status', Expense::STATUS_VOID)])
            ->where('company_id', $this->companyId())
            ->orderBy('name')
            ->get();

        $spend = Expense::where('company_id', $this->companyId())
            ->whereNotIn('status', [Expense::STATUS_VOID, Expense::STATUS_REJECTED, Expense::STATUS_RETURNED])
            ->whereNotNull('category_id')
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return view('accounting.expenses.categories-index', [
            'categories' => $categories,
            'spend' => $spend,
            'cs' => $this->cs(),
        ]);
    }

    public function categoryStore(Request $request)
    {
        $this->requirePermission('expense-categories.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['company_id'] = $this->companyId();
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = (bool) ($request->boolean('is_active'));

        ExpenseCategory::create($validated);

        return redirect()->route('accounting.expenses.categories.index')
            ->with('success', 'Expense category created.');
    }

    public function categoryUpdate(Request $request, ExpenseCategory $category)
    {
        $this->requirePermission('expense-categories.edit');
        $this->authorizeCategoryScope($category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($request->boolean('is_active'));

        $category->update($validated);

        return redirect()->route('accounting.expenses.categories.index')
            ->with('success', 'Expense category updated.');
    }

    public function categoryDestroy(ExpenseCategory $category)
    {
        $this->requirePermission('expense-categories.delete');
        $this->authorizeCategoryScope($category);

        $used = (int) Expense::where('company_id', $this->companyId())->where('category_id', $category->id)->count();

        if ($used > 0) {
            return redirect()->back()->withErrors(['error' => 'This category is used by expenses and cannot be deleted.']);
        }

        $category->delete();

        return redirect()->route('accounting.expenses.categories.index')
            ->with('success', 'Expense category deleted.');
    }

    // ─────────────────────────────────────────────────────────────
    // Reports
    // ─────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $this->requirePermission('expenses.view');

        $companyId = $this->companyId();

        $period = $request->input('period', 'month');
        [$from, $to] = $this->periodRange($period, $request);

        $activeBase = ['draft', 'pending', 'approved', 'posted', 'paid'];

        $query = Expense::where('company_id', $companyId)
            ->whereIn('status', $activeBase)
            ->whereBetween('expense_date', [$from, $to]);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $total = (float) $query->sum('amount');
        $count = (int) $query->count();

        $byCategory = (clone $query)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $maxCategory = max((float) $byCategory->max('total'), 0.01);

        $byDepartment = (clone $query)
            ->whereNotNull('department')
            ->selectRaw('department, sum(amount) as total, count(*) as c')
            ->groupBy('department')
            ->orderByDesc('total')
            ->get();

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        $departments = Expense::where('company_id', $companyId)
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->all();

        return view('accounting.expenses.reports', [
            'total' => $total,
            'count' => $count,
            'byCategory' => $byCategory,
            'maxCategory' => $maxCategory,
            'byDepartment' => $byDepartment,
            'branches' => $branches,
            'departments' => $departments,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'periodLabel' => $this->periodLabel($period, $from, $to),
            'cs' => $this->cs(),
            'filters' => [
                'period' => $period,
                'branch_id' => $request->branch_id,
                'department' => $request->department,
            ],
        ]);
    }

    protected function periodRange(string $period, Request $request): array
    {
        return match ($period) {
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d'),
                $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d'),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    protected function periodLabel(string $period, $from, $to): string
    {
        return match ($period) {
            'quarter' => now()->startOfQuarter()->format('M Y') . ' – ' . now()->endOfQuarter()->format('M Y'),
            'year' => 'Year to date · ' . now()->format('Y'),
            'custom' => $from . ' – ' . $to,
            default => now()->format('F Y'),
        };
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    protected function authorizeScope(Expense $expense): void
    {
        abort_unless((int) $expense->company_id === $this->companyId(), 403);
    }

    protected function authorizeClaimScope(ExpenseClaim $claim): void
    {
        abort_unless((int) $claim->company_id === $this->companyId(), 403);
    }

    protected function authorizeRecurringScope(ExpenseRecurringTemplate $template): void
    {
        abort_unless((int) $template->company_id === $this->companyId(), 403);
    }

    protected function authorizeCategoryScope(ExpenseCategory $category): void
    {
        abort_unless((int) $category->company_id === $this->companyId(), 403);
    }

    protected function handleAttachments(Request $request, Expense $expense): void
    {
        if ($request->has('delete_documents')) {
            foreach ((array) $request->input('delete_documents') as $id) {
                $attachment = $expense->attachments()->where('id', (int) $id)->first();
                if ($attachment) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);
                    $attachment->delete();
                }
            }
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('expense-attachments/' . $expense->company_id . '/' . $expense->id, 'public');

                $expense->attachments()->create([
                    'company_id' => $expense->company_id,
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }

    protected function handleClaimAttachments(Request $request, ExpenseClaim $claim): void
    {
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('expense-claim-attachments/' . $claim->company_id . '/' . $claim->id, 'public');

                $claim->attachments()->create([
                    'company_id' => $claim->company_id,
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }
}
