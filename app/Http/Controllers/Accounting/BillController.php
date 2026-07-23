<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bill;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function __construct(protected BillService $billService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Bill::where('company_id', $companyId)
            ->with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('bill_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('bill_date', '<=', $request->to_date);
        }

        $bills = $query->orderByDesc('bill_date')->paginate(15)->withQueryString();

        return view('accounting.bills.index', compact('bills'));
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
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.bills.create', compact('vendors', 'products', 'expenseAccounts', 'costCenters'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:bill_date'],
            'internal_number' => ['nullable', 'string', 'max:255'],
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
            $bill = $this->billService->create($validated, auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Bill created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Bill $bill)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        $bill->load(['vendor', 'lines.product', 'lines.costCenter', 'journalEntry', 'payments']);

        $payments = $bill->payments()->with('allocations')->get();

        return view('accounting.bills.show', compact('bill', 'payments'));
    }

    public function edit(Bill $bill)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        if (!$bill->isDraft()) {
            abort(403, 'Only draft bills can be edited.');
        }

        $bill->load('lines.product');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.bills.edit', compact('bill', 'vendors', 'products', 'expenseAccounts', 'costCenters'));
    }

    public function update(Request $request, Bill $bill)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        if (!$bill->isDraft()) {
            abort(403, 'Only draft bills can be updated.');
        }

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:bill_date'],
            'internal_number' => ['nullable', 'string', 'max:255'],
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
            $this->billService->update($bill, $validated, auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Bill updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function post(Bill $bill)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        try {
            $this->billService->post($bill, auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Bill posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function approve(Bill $bill)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        try {
            $this->billService->approve($bill, auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Bill approved successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(Bill $bill, Request $request)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->billService->void($bill, $validated['reason'], auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Bill voided successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
