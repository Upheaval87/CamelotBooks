<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Accounting\PaymentService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function create(?int $customerId = null)
    {
        $companyId = session('current_company_id');

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $openInvoices = collect();
        if ($customerId) {
            $openInvoices = Invoice::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
                ->orderByDesc('invoice_date')
                ->get();
        }

        return view('accounting.customer-payments.create', compact('customers', 'bankAccounts', 'openInvoices', 'customerId'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $payment = $this->paymentService->createCustomerPayment($validated, auth()->id());
            $this->paymentService->postCustomerPayment($payment, auth()->id());

            return redirect()->route('accounting.customer-payments.show', $payment)
                ->with('success', 'Customer payment created and posted successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(\App\Models\CustomerPayment $payment)
    {
        $companyId = session('current_company_id');
        abort_unless($payment->company_id == $companyId, 403);

        $payment->load(['customer', 'bankAccount', 'journalEntry', 'allocations.invoice']);

        return view('accounting.customer-payments.show', compact('payment'));
    }
}
