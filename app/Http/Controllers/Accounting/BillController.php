<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\DefaultAccountMapping;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BillController extends Controller
{
    public function __construct(protected BillService $billService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Bill::where('company_id', $companyId)
            ->with(['vendor', 'lines']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('bill_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('bill_date', '<=', $request->to_date);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        $stats = [
            'total' => (int) Bill::where('company_id', $companyId)->count(),
            'amount' => (float) Bill::where('company_id', $companyId)
                ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
                ->selectRaw('COALESCE(SUM(amount), 0) as amt')
                ->value('amt'),
            'due' => (float) Bill::where('company_id', $companyId)
                ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
                ->selectRaw('COALESCE(SUM(amount), 0) - COALESCE(SUM(amount_paid), 0) as due')
                ->value('due'),
            'by_status' => Bill::where('company_id', $companyId)
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status')
                ->toArray(),
        ];

        $bills = $query->orderByDesc('bill_date')->paginate(15)->withQueryString();

        return view('accounting.bills.index', compact('bills', 'stats'));
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedVendorId = $request->input('vendor_id');

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

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = Currency::query()->active()->ordered()->get();

        $defaultExpenseAccountId = DefaultAccountMapping::getAccountId($companyId, 'default_expense')
            ?? $expenseAccounts->first()?->id;

        return view('accounting.bills.create', compact('vendors', 'products', 'expenseAccounts', 'costCenters', 'selectedVendorId', 'branches', 'currencies', 'defaultExpenseAccountId'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:bill_date'],
            'internal_number' => ['nullable', 'string', 'max:255'],
            'po_number' => ['nullable', 'string', 'max:60'],
            'grn_reference' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'supplier_notes' => ['nullable', 'string', 'max:5000'],
            'payment_instructions' => ['nullable', 'string', 'max:5000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'freight_charges' => ['nullable', 'numeric', 'min:0'],
            'insurance_charges' => ['nullable', 'numeric', 'min:0'],
            'customs_charges' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv', 'max:10240'],
            'delete_documents' => ['nullable', 'array'],
            'delete_documents.*' => ['integer'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $bill = $this->billService->create($validated, auth()->id());

            $this->handleAttachments($request, $bill);

            $submitted = $this->handlePostSaveAction($request, $bill);

            if ($request->input('action') === 'save_and_new') {
                return redirect()->route('accounting.bills.create')
                    ->with('success', 'Bill saved. You can add another.');
            }

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', $submitted ? 'Bill submitted for approval.' : 'Bill created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Bill $bill)
    {
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        $bill->load(['vendor', 'lines.product', 'lines.costCenter', 'journalEntry', 'payments', 'attachments']);

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

        $bill->load(['lines.product', 'attachments']);

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

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = Currency::query()->active()->ordered()->get();

        $defaultExpenseAccountId = DefaultAccountMapping::getAccountId($companyId, 'default_expense')
            ?? $expenseAccounts->first()?->id;

        return view('accounting.bills.edit', compact('bill', 'vendors', 'products', 'expenseAccounts', 'costCenters', 'branches', 'currencies', 'defaultExpenseAccountId'));
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
            'po_number' => ['nullable', 'string', 'max:60'],
            'grn_reference' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'supplier_notes' => ['nullable', 'string', 'max:5000'],
            'payment_instructions' => ['nullable', 'string', 'max:5000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'freight_charges' => ['nullable', 'numeric', 'min:0'],
            'insurance_charges' => ['nullable', 'numeric', 'min:0'],
            'customs_charges' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv', 'max:10240'],
            'delete_documents' => ['nullable', 'array'],
            'delete_documents.*' => ['integer'],
        ]);

        try {
            $this->billService->update($bill, $validated, auth()->id());

            $this->handleAttachments($request, $bill);

            $submitted = $this->handlePostSaveAction($request, $bill->fresh());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', $submitted ? 'Bill submitted for approval.' : 'Bill updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle the optional post-save action buttons (Submit for Approval).
     */
    private function handlePostSaveAction(Request $request, Bill $bill): bool
    {
        if ($request->input('action') !== 'submit_for_approval') {
            return false;
        }

        $this->billService->submitForApproval($bill, auth()->id());

        return true;
    }

    /**
     * Persist uploaded attachments and delete any flagged for removal.
     */
    private function handleAttachments(Request $request, Bill $bill): void
    {
        $companyId = $bill->company_id;

        foreach ((array) $request->input('delete_documents', []) as $id) {
            $attachment = Attachment::where('company_id', $companyId)
                ->where('id', (int) $id)
                ->where('attachmentable_type', Bill::class)
                ->where('attachmentable_id', $bill->id)
                ->first();

            if ($attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        foreach ($request->file('files', []) as $file) {
            $path = $file->storeAs(
                "bill-attachments/{$companyId}/{$bill->id}",
                Str::random(24) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $bill->attachments()->create([
                'company_id' => $companyId,
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    public function submit(Bill $bill)
    {
        $this->requirePermission('bills.edit');
        $companyId = session('current_company_id');
        abort_unless($bill->company_id == $companyId, 403);

        try {
            $this->billService->submitForApproval($bill, auth()->id());

            return redirect()->route('accounting.bills.show', $bill)
                ->with('success', 'Bill submitted for approval.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function post(Bill $bill)
    {
        $this->requirePermission('bills.post');
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
        $this->requirePermission('bills.approve');
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
        $this->requirePermission($request, 'bills.void');
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
