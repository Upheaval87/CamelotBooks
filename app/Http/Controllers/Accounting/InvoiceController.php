<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Services\Accounting\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Invoice::where('company_id', $companyId)
            ->with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('invoice_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('invoice_date', '<=', $request->to_date);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderByDesc('invoice_date')->paginate(15)->withQueryString();

        return view('accounting.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('accounting.invoices.create', compact('customers', 'products', 'incomeAccounts', 'costCenters', 'itemCategories'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
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
            'lines.*.income_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $invoice = $this->invoiceService->create($validated, auth()->id());

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $invoice->load(['customer', 'lines.product', 'lines.costCenter', 'journalEntry', 'payments']);

        $payments = $invoice->payments()->with('allocations')->get();

        return view('accounting.invoices.show', compact('invoice', 'payments'));
    }

    public function edit(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        if (!$invoice->isDraft()) {
            abort(403, 'Only draft invoices can be edited.');
        }

        $invoice->load('lines.product');

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('accounting.invoices.edit', compact('invoice', 'customers', 'products', 'incomeAccounts', 'costCenters', 'itemCategories'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        if (!$invoice->isDraft()) {
            abort(403, 'Only draft invoices can be updated.');
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
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
            'lines.*.income_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
        ]);

        try {
            $this->invoiceService->update($invoice, $validated, auth()->id());

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function post(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        try {
            $this->invoiceService->post($invoice, auth()->id());

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(Invoice $invoice, Request $request)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->invoiceService->void($invoice, $validated['reason'], auth()->id());

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice voided successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function printPdf(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $invoice->load(['customer', 'lines.product']);

        return view('accounting.invoices.show', compact('invoice'));
    }
}
