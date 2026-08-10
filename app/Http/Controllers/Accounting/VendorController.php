<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = Vendor::where('company_id', $companyId);

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

        $stats = [
            'total' => (int) Vendor::where('company_id', $companyId)->count(),
            'active' => (int) Vendor::where('company_id', $companyId)->where('is_active', true)->count(),
            'balance_owed' => (float) \App\Models\Bill::where('company_id', $companyId)
                ->whereIn('status', [\App\Models\Bill::STATUS_APPROVED, \App\Models\Bill::STATUS_PARTIALLY_PAID, \App\Models\Bill::STATUS_OVERDUE])
                ->selectRaw('COALESCE(SUM(amount), 0) - COALESCE(SUM(amount_paid), 0) as due')
                ->value('due'),
        ];

        $vendors = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('accounting.vendors.index', compact('vendors', 'stats'));
    }

    public function create()
    {
        return view('accounting.vendors.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'vendors.create');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'remit_to_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        Vendor::create($validated);

        return redirect()->route('accounting.vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $vendor->load(['bills' => function ($q) {
            $q->orderBy('bill_date', 'desc')->limit(50);
        }, 'payments' => function ($q) {
            $q->orderBy('payment_date', 'desc')->limit(50);
        }]);

        $balanceDue = $vendor->balance_due;

        $transactions = collect();

        foreach ($vendor->bills as $bill) {
            $transactions->push([
                'type' => 'Bill',
                'date' => $bill->bill_date,
                'reference' => $bill->bill_number,
                'description' => $bill->memo ?? "Bill {$bill->bill_number}",
                'amount' => $bill->amount,
                'paid' => $bill->amount_paid,
                'balance' => $bill->balance_due,
                'status' => $bill->status,
            ]);
        }

        foreach ($vendor->payments as $payment) {
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

        return view('accounting.vendors.show', compact('vendor', 'balanceDue', 'transactions'));
    }

    public function edit(Vendor $vendor)
    {
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        return view('accounting.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->requirePermission($request, 'vendors.edit');
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'remit_to_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        if (array_key_exists('opening_balance', $validated) && $vendor->opening_balance_date) {
            $hasPostings = \App\Models\JournalEntryLine::where('vendor_id', $vendor->id)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('status', 'posted')
                    ->where('date', '>=', $vendor->opening_balance_date)
                )
                ->exists();

            if (!$hasPostings) {
                $hasPostings = \App\Models\Bill::where('vendor_id', $vendor->id)
                    ->where('bill_date', '>=', $vendor->opening_balance_date)
                    ->exists();
            }

            if (!$hasPostings) {
                $hasPostings = \App\Models\VendorPayment::where('vendor_id', $vendor->id)
                    ->where('payment_date', '>=', $vendor->opening_balance_date)
                    ->exists();
            }

            if ($hasPostings) {
                unset($validated['opening_balance']);
                $validated['opening_balance_date'] = null;
            }
        }

        $vendor->update($validated);

        return redirect()->route('accounting.vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }

    public function toggle(Vendor $vendor)
    {
        $this->requirePermission('vendors.void');
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $vendor->update(['is_active' => !$vendor->is_active]);

        $status = $vendor->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.vendors.index')
            ->with('success', "Vendor {$status} successfully.");
    }
}
