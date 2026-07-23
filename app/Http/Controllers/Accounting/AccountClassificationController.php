<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Http\Request;

class AccountClassificationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        return view('accounting.account-classification.index', compact('accounts'));
    }

    public function update(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'cash_flow_sections' => 'array',
            'is_non_cash' => 'array',
        ]);

        $cashFlowSections = $validated['cash_flow_sections'] ?? [];
        $isNonCash = $validated['is_non_cash'] ?? [];

        $accounts = Account::where('company_id', $companyId)->active()->get();

        foreach ($accounts as $account) {
            $updates = [];

            if (array_key_exists($account->id, $cashFlowSections)) {
                $val = $cashFlowSections[$account->id];
                $updates['cash_flow_section'] = $val === '' ? null : $val;
            }

            $updates['is_non_cash'] = array_key_exists($account->id, $isNonCash);

            $account->update($updates);
        }

        return redirect()->route('accounting.account-classification.index')
            ->with('success', 'Account classifications updated successfully.');
    }
}
