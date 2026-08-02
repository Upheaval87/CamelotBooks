<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Services\Accounting\VendorCreditService;
use Illuminate\Http\Request;

class VendorCreditController extends Controller
{
    public function __construct(protected VendorCreditService $vendorCreditService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
    {
        $companyId = session('current_company_id');

        $query = VendorCredit::where('company_id', $companyId)
            ->with(['vendor', 'bill']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('credit_note_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('credit_note_date', '<=', $request->to_date);
        }

        $vendorCredits = $query->orderByDesc('credit_note_date')->paginate(15)->withQueryString();

        return view('accounting.vendor-credits.index', compact('vendorCredits'));
    }

    public function create(Request $request, ?int $billId = null)
    {
        $companyId = session('current_company_id');
        $selectedVendorId = $billId ? null : $request->input('vendor_id');

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

        $bill = null;
        if ($billId) {
            $bill = Bill::where('id', $billId)
                ->where('company_id', $companyId)
                ->first();
            abort_unless($bill, 404);
        }

        $bills = Bill::where('company_id', $companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID])
            ->orderByDesc('bill_date')
            ->get();

        return view('accounting.vendor-credits.create', compact('vendors', 'products', 'expenseAccounts', 'bill', 'bills', 'billId', 'selectedVendorId'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'credit_note_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'bill_id' => ['nullable', 'integer', 'exists:bills,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $vendorCredit = $this->vendorCreditService->create($validated, auth()->id());

            return redirect()->route('accounting.vendor-credits.show', $vendorCredit)
                ->with('success', 'Vendor credit created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(VendorCredit $vendorCredit)
    {
        $companyId = session('current_company_id');
        abort_unless($vendorCredit->company_id == $companyId, 403);

        $vendorCredit->load(['vendor', 'lines.product', 'journalEntry', 'bill']);

        $allocations = $vendorCredit->allocations()->with('bill')->get();

        return view('accounting.vendor-credits.show', compact('vendorCredit', 'allocations'));
    }

    public function post(VendorCredit $vendorCredit)
    {
        $this->requirePermission('vendor-credits.post');
        $companyId = session('current_company_id');
        abort_unless($vendorCredit->company_id == $companyId, 403);

        try {
            $this->vendorCreditService->post($vendorCredit, auth()->id());

            return redirect()->route('accounting.vendor-credits.show', $vendorCredit)
                ->with('success', 'Vendor credit posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function applyForm(VendorCredit $vendorCredit)
    {
        $companyId = session('current_company_id');
        abort_unless($vendorCredit->company_id == $companyId, 403);

        if ($vendorCredit->status !== VendorCredit::STATUS_POSTED) {
            abort(403, 'Only posted vendor credits can be applied.');
        }

        $vendorCredit->load('vendor');

        $openBills = Bill::where('company_id', $companyId)
            ->where('vendor_id', $vendorCredit->vendor_id)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID])
            ->orderByDesc('bill_date')
            ->get();

        $availableAmount = $vendorCredit->amount - $vendorCredit->amount_applied;

        return view('accounting.vendor-credits.apply', compact('vendorCredit', 'openBills', 'availableAmount'));
    }

    public function apply(Request $request, VendorCredit $vendorCredit)
    {
        $companyId = session('current_company_id');
        abort_unless($vendorCredit->company_id == $companyId, 403);

        $validated = $request->validate([
            'bill_id' => ['required', 'integer', 'exists:bills,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->vendorCreditService->apply($vendorCredit, $validated['bill_id'], (float) $validated['amount']);

            return redirect()->route('accounting.vendor-credits.show', $vendorCredit)
                ->with('success', 'Vendor credit applied successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(VendorCredit $vendorCredit, Request $request)
    {
        $this->requirePermission($request, 'vendor-credits.void');
        $companyId = session('current_company_id');
        abort_unless($vendorCredit->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->vendorCreditService->void($vendorCredit, $validated['reason'], auth()->id());

            return redirect()->route('accounting.vendor-credits.show', $vendorCredit)
                ->with('success', 'Vendor credit voided successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
