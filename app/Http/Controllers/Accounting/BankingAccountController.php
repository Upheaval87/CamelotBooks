<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Services\Accounting\BankService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankingAccountController extends Controller
{
    public function __construct(protected BankService $bankService)
    {
    }

    public function index()
    {
        $companyId = (int) session('current_company_id');

        $accounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        $totalBalance = round($accounts->sum(fn ($a) => (float) $a->current_balance), 2);

        return view('accounting.banking.accounts-index', compact('accounts', 'totalBalance'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

        $company = Company::find($companyId);
        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.banking.account-form', [
            'account' => null,
            'company' => $company,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'bank-accounts.create');
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'date'],
            'next_cheque_number' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Account::create([
            'company_id' => $companyId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'description' => $validated['description'] ?? null,
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'opening_balance_date' => $validated['opening_balance_date'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'is_bank_account' => true,
            'is_petty_cash' => false,
            'is_active' => true,
            'next_cheque_number' => $validated['next_cheque_number'] ?? 1001,
        ]);

        return redirect()->route('accounting.banking.accounts')->with('success', 'Bank account created successfully.');
    }

    public function edit(int $accountId)
    {
        $account = $this->findBankAccount($accountId);

        $company = Company::find($account->company_id);
        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.banking.account-form', compact('account', 'company', 'currencies'));
    }

    public function update(Request $request, int $accountId)
    {
        $this->requirePermission($request, 'bank-accounts.edit');
        $account = $this->findBankAccount($accountId);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('accounts', 'code')->ignore($account->id)],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'date'],
            'next_cheque_number' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $account->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'opening_balance_date' => $validated['opening_balance_date'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'next_cheque_number' => $validated['next_cheque_number'] ?? $account->next_cheque_number,
        ]);

        return redirect()->route('accounting.banking.accounts')->with('success', 'Bank account updated successfully.');
    }

    public function toggle(int $accountId)
    {
        $this->requirePermission('bank-accounts.edit');
        $account = $this->findBankAccount($accountId);

        $account->update(['is_active' => ! $account->is_active]);

        return redirect()->route('accounting.banking.accounts')
            ->with('success', $account->is_active ? 'Bank account activated.' : 'Bank account deactivated.');
    }

    protected function findBankAccount(int $accountId): Account
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', (int) session('current_company_id'))
            ->where('is_bank_account', true)
            ->first();

        abort_unless($account, 404);

        return $account;
    }
}
