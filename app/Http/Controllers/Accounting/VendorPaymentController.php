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
            $payment = $this->paymentService->createVendorPayment($validated, auth()->id());
            $this->paymentService->postVendorPayment($payment, auth()->id());

            return redirect()->route('accounting.vendor-payments.show', $payment)
                ->with('success', 'Vendor payment created and posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
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
