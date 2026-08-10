<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Services\Accounting\CreditNoteService;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function __construct(protected CreditNoteService $creditNoteService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = CreditNote::where('company_id', $companyId)
            ->with(['customer', 'invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('credit_note_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('credit_note_date', '<=', $request->to_date);
        }

        $stats = [
            'total' => (int) CreditNote::where('company_id', $companyId)->count(),
            'amount' => (float) CreditNote::where('company_id', $companyId)
                ->where('status', '!=', CreditNote::STATUS_VOID)
                ->selectRaw('COALESCE(SUM(amount), 0) as amt')
                ->value('amt'),
            'applied' => (float) CreditNote::where('company_id', $companyId)
                ->selectRaw('COALESCE(SUM(amount_applied), 0) as amt')
                ->value('amt'),
            'by_status' => CreditNote::where('company_id', $companyId)
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status')
                ->toArray(),
        ];

        $creditNotes = $query->orderByDesc('credit_note_date')->paginate(15)->withQueryString();

        return view('accounting.credit-notes.index', compact('creditNotes', 'stats'));
    }

    public function create(?int $invoiceId = null)
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

        $invoice = null;
        if ($invoiceId) {
            $invoice = Invoice::where('id', $invoiceId)
                ->where('company_id', $companyId)
                ->first();
            abort_unless($invoice, 404);
        }

        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->orderByDesc('invoice_date')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('accounting.credit-notes.create', compact('customers', 'products', 'incomeAccounts', 'invoice', 'invoices', 'invoiceId', 'itemCategories'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'credit_note_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.income_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $creditNote = $this->creditNoteService->create($validated, auth()->id());

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(CreditNote $creditNote)
    {
        $companyId = session('current_company_id');
        abort_unless($creditNote->company_id == $companyId, 403);

        $creditNote->load(['customer', 'lines.product', 'journalEntry', 'invoice']);

        $allocations = $creditNote->allocations()->with('invoice')->get();

        return view('accounting.credit-notes.show', compact('creditNote', 'allocations'));
    }

    public function post(CreditNote $creditNote)
    {
        $this->requirePermission('credit-notes.post');
        $companyId = session('current_company_id');
        abort_unless($creditNote->company_id == $companyId, 403);

        try {
            $this->creditNoteService->post($creditNote, auth()->id());

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function applyForm(CreditNote $creditNote)
    {
        $companyId = session('current_company_id');
        abort_unless($creditNote->company_id == $companyId, 403);

        if ($creditNote->status !== CreditNote::STATUS_POSTED) {
            abort(403, 'Only posted credit notes can be applied.');
        }

        $creditNote->load('customer');

        $openInvoices = Invoice::where('company_id', $companyId)
            ->where('customer_id', $creditNote->customer_id)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
            ->orderByDesc('invoice_date')
            ->get();

        $availableAmount = $creditNote->amount - $creditNote->amount_applied;

        return view('accounting.credit-notes.apply', compact('creditNote', 'openInvoices', 'availableAmount'));
    }

    public function apply(Request $request, CreditNote $creditNote)
    {
        $companyId = session('current_company_id');
        abort_unless($creditNote->company_id == $companyId, 403);

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->creditNoteService->apply($creditNote, $validated['invoice_id'], (float) $validated['amount']);

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note applied successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(CreditNote $creditNote, Request $request)
    {
        $this->requirePermission($request, 'credit-notes.void');
        $companyId = session('current_company_id');
        abort_unless($creditNote->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->creditNoteService->void($creditNote, $validated['reason'], auth()->id());

            return redirect()->route('accounting.credit-notes.show', $creditNote)
                ->with('success', 'Credit note voided successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
