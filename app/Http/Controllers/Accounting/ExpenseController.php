<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Accounting\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenseService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Expense::where('company_id', $companyId)
            ->with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $expenses = $query->orderByDesc('expense_date')->paginate(15)->withQueryString();

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.expenses.index', compact('expenses', 'vendors'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

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

        return view('accounting.expenses.create', compact('vendors', 'products', 'expenseAccounts', 'bankAccounts', 'costCenters'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'expense_date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $expense = $this->expenseService->create($validated, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Expense $expense)
    {
        $companyId = session('current_company_id');
        abort_unless($expense->company_id == $companyId, 403);

        $expense->load(['vendor', 'bankAccount', 'lines.product', 'lines.costCenter', 'lines.expenseAccount', 'journalEntry', 'createdByUser']);

        return view('accounting.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $companyId = session('current_company_id');
        abort_unless($expense->company_id == $companyId, 403);

        if (!$expense->isDraft()) {
            abort(403, 'Only draft expenses can be edited.');
        }

        $expense->load('lines.product');

        $companyId = session('current_company_id');

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

        return view('accounting.expenses.edit', compact('expense', 'vendors', 'products', 'expenseAccounts', 'bankAccounts', 'costCenters'));
    }

    public function update(Request $request, Expense $expense)
    {
        $companyId = session('current_company_id');
        abort_unless($expense->company_id == $companyId, 403);

        if (!$expense->isDraft()) {
            abort(403, 'Only draft expenses can be updated.');
        }

        $validated = $request->validate([
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'expense_date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
        ]);

        try {
            $this->expenseService->update($expense, $validated, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function post(Expense $expense)
    {
        $this->requirePermission('expenses.post');
        $companyId = session('current_company_id');
        abort_unless($expense->company_id == $companyId, 403);

        try {
            $this->expenseService->post($expense, auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(Expense $expense, Request $request)
    {
        $this->requirePermission($request, 'expenses.void');
        $companyId = session('current_company_id');
        abort_unless($expense->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->expenseService->void($expense, $validated['reason'], auth()->id());

            return redirect()->route('accounting.expenses.show', $expense)
                ->with('success', 'Expense voided successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
