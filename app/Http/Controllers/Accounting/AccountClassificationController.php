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

    public function create()
    {
        $companyId = session('current_company_id');
        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();

        return view('accounting.account-classification.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'statement' => 'required|in:balance_sheet,income_statement',
            'section' => 'required|string|max:100',
            'display_order' => 'required|integer|min:0',
            'account_ids' => 'sometimes|array',
            'account_ids.*' => 'exists:accounts,id',
        ]);

        $accounts = Account::where('company_id', $companyId)->active()->get();
        foreach ($accounts as $account) {
            if (in_array($account->id, $validated['account_ids'] ?? [])) {
                $account->update([
                    'cash_flow_section' => $validated['section'],
                ]);
            }
        }

        return redirect()->route('accounting.account-classification.index')
            ->with('success', 'Classification "' . $validated['name'] . '" created successfully.');
    }

    public function edit($classification)
    {
        $companyId = session('current_company_id');
        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();

        return view('accounting.account-classification.create', [
            'accounts' => $accounts,
            'classification' => [
                'id' => $classification,
                'name' => ucwords(str_replace('-', ' ', $classification)),
                'statement' => str_contains($classification, 'asset') || str_contains($classification, 'liabilit') || str_contains($classification, 'equity') ? 'balance_sheet' : 'income_statement',
                'section' => $classification,
                'display_order' => 10,
            ],
        ]);
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
