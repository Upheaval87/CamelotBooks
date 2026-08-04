<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosPaymentMethod;
use App\Models\Account;
use Illuminate\Http\Request;

class PosPaymentMethodController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->with('clearingAccount', 'settlementBankAccount')
            ->latest()
            ->get();
        $clearingAccounts = Account::where('company_id', $companyId)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('pos.payment-methods.index', compact('paymentMethods', 'clearingAccounts', 'accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:pos_payment_methods,name,NULL,id,company_id,' . $companyId,
            'type' => 'required|in:cash,card,mobile_money',
            'clearing_account_id' => 'nullable|exists:accounts,id',
            'settlement_bank_account_id' => 'nullable|exists:accounts,id',
            'requires_reference' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;
        $validated['requires_reference'] = $request->boolean('requires_reference');

        PosPaymentMethod::create($validated);

        return redirect()->route('pos.payment-methods.index')->with('success', 'Payment method created successfully.');
    }

    public function update(Request $request, PosPaymentMethod $paymentMethod)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:pos_payment_methods,name,' . $paymentMethod->id . ',id,company_id,' . $companyId,
            'type' => 'required|in:cash,card,mobile_money',
            'clearing_account_id' => 'nullable|exists:accounts,id',
            'settlement_bank_account_id' => 'nullable|exists:accounts,id',
            'requires_reference' => 'boolean',
        ]);

        $validated['requires_reference'] = $request->boolean('requires_reference');

        $paymentMethod->update($validated);

        return redirect()->route('pos.payment-methods.index')->with('success', 'Payment method updated successfully.');
    }

    public function toggle(PosPaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);
        $status = $paymentMethod->is_active ? 'activated' : 'deactivated';

        return redirect()->route('pos.payment-methods.index')->with('success', "Payment method {$status} successfully.");
    }
}
