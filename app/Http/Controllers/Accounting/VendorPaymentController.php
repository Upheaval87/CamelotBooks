<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Services\Accounting\PaymentService;
use Illuminate\Http\Request;

class VendorPaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function index(Request $request)
    {
        $this->requirePermission($request, 'vendor-payments.view');
        $companyId = session('current_company_id');

        $query = VendorPayment::where('company_id', $companyId)
            ->with(['vendor', 'bankAccount', 'allocations']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from_date')) {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $activeStatus = $request->input('status', '');

        if ($activeStatus === 'pending_approval') {
            $query->where('status', VendorPayment::STATUS_PENDING_APPROVAL);
        } elseif ($activeStatus === 'reversed') {
            $query->where('status', VendorPayment::STATUS_REVERSED);
        } elseif ($activeStatus === 'posted') {
            $query->where('status', VendorPayment::STATUS_POSTED);
        } elseif ($activeStatus === 'draft') {
            $query->where('status', VendorPayment::STATUS_DRAFT);
        } elseif ($activeStatus === 'rejected') {
            $query->where('status', VendorPayment::STATUS_REJECTED);
        }

        $base = VendorPayment::where('company_id', $companyId);

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $stats = [
            'total' => (int) (clone $base)->count(),
            'amount' => (float) (clone $base)->selectRaw('COALESCE(SUM(amount), 0) as amt')->value('amt'),
            'pending_approval' => (int) (clone $base)->where('status', VendorPayment::STATUS_PENDING_APPROVAL)->count(),
            'pending_approval_amount' => (float) (clone $base)
                ->where('status', VendorPayment::STATUS_PENDING_APPROVAL)
                ->selectRaw('COALESCE(SUM(amount), 0) as amt')->value('amt'),
            'posted' => (int) (clone $base)->where('status', VendorPayment::STATUS_POSTED)->count(),
            'posted_month' => (int) (clone $base)
                ->where('status', VendorPayment::STATUS_POSTED)
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->count(),
            'posted_month_amount' => (float) (clone $base)
                ->where('status', VendorPayment::STATUS_POSTED)
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(amount), 0) as amt')->value('amt'),
            'draft' => (int) (clone $base)->where('status', VendorPayment::STATUS_DRAFT)->count(),
            'rejected' => (int) (clone $base)->where('status', VendorPayment::STATUS_REJECTED)->count(),
            'reversed' => (int) (clone $base)->where('status', VendorPayment::STATUS_REVERSED)->count(),
        ];

        $approvalQueue = VendorPayment::where('company_id', $companyId)
            ->where('status', VendorPayment::STATUS_PENDING_APPROVAL)
            ->with(['vendor', 'bankAccount'])
            ->orderByDesc('payment_date')
            ->limit(10)
            ->get();

        $payments = $query->orderByDesc('payment_date')->paginate(15)->withQueryString();

        return view('accounting.vendor-payments.index', compact('payments', 'stats', 'approvalQueue', 'activeStatus'));
    }

    public function create(Request $request, ?int $vendorId = null)
    {
        $vendorId = $vendorId ?: ((int) $request->input('vendor_id', 0) ?: null);
        $companyId = session('current_company_id');

        $vendors = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $openBillsByVendor = Vendor::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['bills' => function ($q) {
                $q->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID])
                    ->orderByDesc('bill_date')
                    ->limit(100);
            }])
            ->get()
            ->mapWithKeys(fn ($vendor) => [
                (string) $vendor->id => $vendor->bills->map(fn ($bill) => [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'bill_date' => $bill->bill_date?->format('Y-m-d'),
                    'amount' => (float) $bill->amount,
                    'balance_due' => (float) $bill->balance_due,
                ])->values()->toArray(),
            ])
            ->toArray();

        $openBills = collect($openBillsByVendor[(string) $vendorId] ?? []);

        $preselectVendor = $vendorId ? $vendors->firstWhere('id', $vendorId) : null;

        return view('accounting.vendor-payments.create', compact(
            'vendors', 'bankAccounts', 'openBills', 'openBillsByVendor', 'vendorId', 'preselectVendor'
        ));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $action = $request->input('action', 'save_draft');

        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.bill_id' => ['required', 'integer', 'exists:bills,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $status = $action === 'approve_post'
                ? VendorPayment::STATUS_PENDING_APPROVAL
                : VendorPayment::STATUS_DRAFT;

            $payment = $this->paymentService->createVendorPayment($validated, auth()->id(), $status);

            if ($action === 'approve_post') {
                $this->paymentService->postVendorPayment($payment, auth()->id());
                $payment = $payment->fresh();

                return redirect()->route('accounting.vendor-payments.show', $payment)
                    ->with('success', 'Vendor payment created and posted successfully.');
            }

            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->with('success', 'Vendor payment draft saved.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function submit(VendorPayment $payment)
    {
        $companyId = session('current_company_id');
        abort_unless($payment->company_id == $companyId, 403);

        try {
            $this->paymentService->submitVendorPayment($payment, auth()->id());

            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->with('success', 'Vendor payment submitted for approval.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function approve(VendorPayment $payment)
    {
        $companyId = session('current_company_id');
        abort_unless($payment->company_id == $companyId, 403);

        try {
            $this->paymentService->postVendorPayment($payment, auth()->id());

            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->with('success', 'Vendor payment approved and posted.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, VendorPayment $payment)
    {
        $companyId = session('current_company_id');
        abort_unless($payment->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->paymentService->rejectVendorPayment($payment, auth()->id(), $validated['reason']);

            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->with('success', 'Vendor payment rejected.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(VendorPayment $payment)
    {
        $companyId = session('current_company_id');
        abort_unless($payment->company_id == $companyId, 403);

        $payment->load(['vendor', 'bankAccount', 'journalEntry', 'allocations.bill.lines']);

        return view('accounting.vendor-payments.show', compact('payment'));
    }
}
