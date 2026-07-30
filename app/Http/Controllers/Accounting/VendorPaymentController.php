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

    public function create(?int $vendorId = null)
    {
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

        $openBills = collect();
        if ($vendorId) {
            $openBills = Bill::where('company_id', $companyId)
                ->where('vendor_id', $vendorId)
                ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID])
                ->orderByDesc('bill_date')
                ->limit(100)
                ->get();
        }

        return view('accounting.vendor-payments.create', compact('vendors', 'bankAccounts', 'openBills', 'vendorId'));
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

        $payment->load(['vendor', 'bankAccount', 'journalEntry', 'allocations.bill']);

        return view('accounting.vendor-payments.show', compact('payment'));
    }
}
