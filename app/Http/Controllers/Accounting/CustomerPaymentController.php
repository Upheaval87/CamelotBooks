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

    public function create(Request $request, ?int $customerId = null)
    {
        $companyId = session('current_company_id');

        $customerId = $customerId ?: ((int) $request->query('customer_id', 0) ?: null);

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $openInvoicesByCustomer = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['invoices' => function ($q) {
                $q->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID])
                    ->orderByDesc('invoice_date')
                    ->limit(100);
            }])
            ->get()
            ->mapWithKeys(fn ($customer) => [
                (string) $customer->id => $customer->invoices->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                    'amount' => (float) $invoice->amount,
                    'balance_due' => (float) $invoice->balance_due,
                ])->values()->toArray(),
            ])
            ->toArray();

        $openInvoices = collect($openInvoicesByCustomer[(string) $customerId] ?? []);

        $preselectCustomer = $customerId ? $customers->firstWhere('id', $customerId) : null;

        return view('accounting.customer-payments.create', compact(
            'customers', 'bankAccounts', 'openInvoices', 'openInvoicesByCustomer', 'customerId', 'preselectCustomer'
        ));
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
