<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Services\Accounting\BankService;
use Illuminate\Http\Request;

class BankingRegisterController extends Controller
{
    public function __construct(protected BankService $bankService)
    {
    }

    public function index(int $accountId, Request $request)
    {
        $companyId = (int) session('current_company_id');

        $bankAccount = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $transactions = $this->bankService->getRegister($accountId, $companyId, $fromDate, $toDate);

        $openingBalance = (float) $bankAccount->opening_balance;

        if ($fromDate) {
            $openingBalance += (float) BankTransaction::where('bank_account_id', $accountId)
                ->where('company_id', $companyId)
                ->where('date', '<', $fromDate)
                ->sum('amount');
        }

        $reconciledBalance = $this->bankService->getReconciledBalance($accountId);

        return view('accounting.banking.register', compact(
            'bankAccount',
            'transactions',
            'openingBalance',
            'reconciledBalance',
            'fromDate',
            'toDate'
        ));
    }

    public function newTransaction(int $accountId)
    {
        $companyId = (int) session('current_company_id');

        $bankAccount = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_bank_account', false)
            ->orderBy('code')
            ->get();

        return view('accounting.banking.new-transaction', compact('bankAccount', 'accounts'));
    }

    public function storeTransaction(Request $request)
    {
        $this->requirePermission($request, 'bank-accounts.create');
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'type' => ['required', 'string', 'in:fee,withdrawal,deposit,interest'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'debit_account_id' => ['required_if:type,fee,withdrawal', 'nullable', 'integer', 'exists:accounts,id'],
            'credit_account_id' => ['required_if:type,deposit,interest', 'nullable', 'integer', 'exists:accounts,id'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $transaction = $this->bankService->createManualTransaction($validated, auth()->id());

            return redirect()->route('accounting.banking.register', $validated['bank_account_id'])
                ->with('success', 'Transaction created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
