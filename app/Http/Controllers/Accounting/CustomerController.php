<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Customer::where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('terms')) {
            $query->where('payment_terms', $request->terms);
        }

        $stats = [
            'total' => (int) Customer::where('company_id', $companyId)->count(),
            'active' => (int) Customer::where('company_id', $companyId)->where('is_active', true)->count(),
            'balance_owed' => (float) \App\Models\Invoice::where('company_id', $companyId)
                ->whereIn('status', [\App\Models\Invoice::STATUS_SENT, \App\Models\Invoice::STATUS_PARTIALLY_PAID, \App\Models\Invoice::STATUS_OVERDUE])
                ->selectRaw('COALESCE(SUM(amount), 0) - COALESCE(SUM(amount_paid), 0) as due')
                ->value('due'),
        ];

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('accounting.customers.index', compact('customers', 'stats'));
    }

    public function create()
    {
        return view('accounting.customers.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'customers.create');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        Customer::create($validated);

        return redirect()->route('accounting.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $companyId = session('current_company_id');
        abort_unless($customer->company_id == $companyId, 403);

        $customer->load(['invoices' => function ($q) {
            $q->orderBy('invoice_date', 'desc')->limit(50);
        }, 'payments' => function ($q) {
            $q->orderBy('payment_date', 'desc')->limit(50);
        }]);

        $balanceDue = $customer->balance_due;

        $transactions = collect();

        foreach ($customer->invoices as $invoice) {
            $transactions->push([
                'type' => 'Invoice',
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'description' => $invoice->memo ?? "Invoice {$invoice->invoice_number}",
                'amount' => $invoice->amount,
                'paid' => $invoice->amount_paid,
                'balance' => $invoice->balance_due,
                'status' => $invoice->status,
            ]);
        }

        foreach ($customer->payments as $payment) {
            $transactions->push([
                'type' => 'Payment',
                'date' => $payment->payment_date,
                'reference' => $payment->payment_number,
                'description' => $payment->memo ?? "Payment {$payment->payment_number}",
                'amount' => -$payment->amount,
                'paid' => 0,
                'balance' => -$payment->amount,
                'status' => 'paid',
            ]);
        }

        $transactions = $transactions->sortByDesc('date')->values();

        return view('accounting.customers.show', compact('customer', 'balanceDue', 'transactions'));
    }

    public function edit(Customer $customer)
    {
        $companyId = session('current_company_id');
        abort_unless($customer->company_id == $companyId, 403);

        return view('accounting.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->requirePermission($request, 'customers.edit');
        $companyId = session('current_company_id');
        abort_unless($customer->company_id == $companyId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        if (array_key_exists('opening_balance', $validated) && $customer->opening_balance_date) {
            $hasPostings = \App\Models\Invoice::where('customer_id', $customer->id)
                ->where('invoice_date', '>=', $customer->opening_balance_date)
                ->exists();

            if (!$hasPostings) {
                $hasPostings = \App\Models\CustomerPayment::where('customer_id', $customer->id)
                    ->where('payment_date', '>=', $customer->opening_balance_date)
                    ->exists();
            }

            if ($hasPostings) {
                unset($validated['opening_balance']);
                $validated['opening_balance_date'] = null;
            }
        }

        $customer->update($validated);

        return redirect()->route('accounting.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function toggle(Customer $customer)
    {
        $this->requirePermission('customers.void');
        $companyId = session('current_company_id');
        abort_unless($customer->company_id == $companyId, 403);

        $customer->update(['is_active' => !$customer->is_active]);

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.customers.index')
            ->with('success', "Customer {$status} successfully.");
    }

    public function search(Request $request)
    {
        $companyId = session('current_company_id');
        $search = $request->input('q', '');

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }
}
