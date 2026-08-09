<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Quotation;
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

        $sort = $request->input('sort', 'date-desc');

        $query = $this->baseQuery($request);
        foreach ($this->orderByFor($sort) as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $invoices = $query->paginate(15)->withQueryString();

        $stats = Invoice::where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $statsTotal = Invoice::where('company_id', $companyId)->count();

        return view('accounting.invoices.index', compact('invoices', 'stats', 'statsTotal', 'sort'));
    }

    private function baseQuery(Request $request)
    {
        $companyId = session('current_company_id');

        return Invoice::where('company_id', $companyId)
            ->with('customer')
            ->when($request->status === 'open', fn ($q) => $q->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT]))
            ->when($request->filled('status') && $request->status !== 'open', fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from_date'), fn ($q) => $q->where('invoice_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->where('invoice_date', '<=', $request->to_date))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            });
    }

    private function orderByFor(string $sort): array
    {
        return match ($sort) {
            'date-asc' => ['invoice_date' => 'asc', 'id' => 'asc'],
            'amount-desc' => ['amount' => 'desc', 'invoice_date' => 'desc'],
            'amount-asc' => ['amount' => 'asc', 'invoice_date' => 'asc'],
            'status' => ['status' => 'asc', 'invoice_date' => 'desc'],
            default => ['invoice_date' => 'desc', 'id' => 'desc'],
        };
    }

    public function create(Request $request)
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

        $copyQuote = null;
        if ($request->filled('copy_quote')) {
            $quote = Quotation::with(['customer', 'lines.product'])
                ->where('company_id', $companyId)
                ->find($request->integer('copy_quote'));

            if ($quote && in_array($quote->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
                $copyQuote = $this->copyQuotePayload($quote);
            }
        }

        $preselectCustomer = null;
        if (!$copyQuote && $request->filled('customer_id')) {
            $customer = $customers->firstWhere('id', (int) $request->integer('customer_id'));
            if ($customer) {
                $preselectCustomer = ['id' => $customer->id, 'name' => $customer->name];
            }
        }

        $copyQuotes = $this->copyQuotesQuery($companyId)->get();

        return view('accounting.invoices.create', compact('customers', 'products', 'incomeAccounts', 'costCenters', 'itemCategories', 'copyQuote', 'preselectCustomer', 'copyQuotes'));
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

        $copyQuotes = $this->copyQuotesQuery($companyId, $invoice->customer_id)->get();

        return view('accounting.invoices.show', compact('invoice', 'payments', 'copyQuotes'));
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

        $copyQuotes = $this->copyQuotesQuery($companyId, $invoice->customer_id)->get();

        return view('accounting.invoices.edit', compact('invoice', 'customers', 'products', 'incomeAccounts', 'costCenters', 'itemCategories', 'copyQuotes'));
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
        $this->requirePermission('invoices.post');
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
        $this->requirePermission($request, 'invoices.void');
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

    public function copyQuote(Request $request)
    {
        $companyId = session('current_company_id');

        $request->validate([
            'quotation' => ['required', 'integer'],
        ]);

        $quote = Quotation::with(['customer', 'lines.product'])
            ->where('company_id', $companyId)
            ->findOrFail($request->integer('quotation'));

        abort_unless(in_array($quote->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED], true), 422, 'Only sent or accepted quotations can be copied to an invoice.');

        return response()->json($this->copyQuotePayload($quote));
    }

    protected function copyQuotesQuery(int $companyId, ?int $customerId = null)
    {
        return Quotation::with('customer')
            ->where('company_id', $companyId)
            ->whereIn('status', [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderByDesc('quotation_date')
            ->limit(25);
    }

    protected function copyQuotePayload(Quotation $quote): array
    {
        $lines = $quote->lines;
        $accountLabels = Account::whereIn('id', $lines->pluck('income_account_id')->filter()->unique())
            ->get(['id', 'code', 'name'])
            ->keyBy('id');
        $ccLabels = CostCenter::whereIn('id', $lines->pluck('cost_center_id')->filter()->unique())
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        return [
            'quotation_number' => $quote->quotation_number,
            'customer_id' => $quote->customer_id,
            'customer_name' => $quote->customer?->name ?? '',
            'customer_contact' => $quote->customer?->display_name ?? $quote->customer?->name ?? '',
            'customer_email' => $quote->customer?->email ?? '',
            'customer_phone' => $quote->customer?->phone ?? '',
            'customer_terms' => $quote->customer?->payment_terms ?? '',
            'memo' => "Converted from Quotation {$quote->quotation_number}",
            'reference' => $quote->reference,
            'currency' => $quote->currency,
            'total' => (float) $quote->total,
            'lines' => $lines->map(function ($line) use ($accountLabels, $ccLabels) {
                $account = $accountLabels->get($line->income_account_id);

                return [
                    'product_id' => $line->product_id,
                    'label' => $line->product?->name ?? '',
                    'sku' => $line->product?->sku ?? '',
                    'description' => $line->description,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'discount' => (float) $line->discount,
                    'tax_rate' => (float) $line->tax_rate,
                    'income_account_id' => $line->income_account_id,
                    'income_account_label' => $account ? "{$account->code} - {$account->name}" : '',
                    'cost_center_id' => $line->cost_center_id,
                    'cost_center_label' => ($cc = $ccLabels->get($line->cost_center_id)) ? "{$cc->code} - {$cc->name}" : '',
                    'amount' => (float) $line->amount,
                    'tax_amount' => (float) $line->tax_amount,
                    'line_total' => (float) $line->line_total,
                ];
            })->values()->all(),
        ];
    }

    public function printPdf(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $invoice->load(['customer', 'lines.product']);

        return view('accounting.invoices.show', compact('invoice'));
    }
}
