<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\BankService;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function __construct(protected BankService $bankService)
    {
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        return view('accounting.bank.index', compact('bankAccounts'));
    }

    public function register(int $bankAccountId, Request $request)
    {
        $companyId = session('current_company_id');

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $transactions = $this->bankService->getRegister($bankAccountId, $companyId, $fromDate, $toDate);

        $reconciledBalance = $this->bankService->getReconciledBalance($bankAccountId);

        return view('accounting.bank.register', compact('bankAccount', 'transactions', 'reconciledBalance', 'fromDate', 'toDate'));
    }

    public function transferForm()
    {
        $companyId = session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.bank.transfer', compact('bankAccounts'));
    }

    public function transfer(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->bankService->transfer(
                $validated['from_account_id'],
                $validated['to_account_id'],
                (float) $validated['amount'],
                $validated['date'],
                $validated['description'],
                $companyId,
                auth()->id()
            );

            return redirect()->route('accounting.bank.index')
                ->with('success', 'Transfer completed successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function manualTransactionForm(int $bankAccountId)
    {
        $companyId = session('current_company_id');

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 404);

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_bank_account', false)
            ->orderBy('code')
            ->get();

        return view('accounting.bank.manual-transaction', compact('bankAccount', 'accounts'));
    }

    public function storeManualTransaction(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'type' => ['required', 'string', 'in:fee,withdrawal,deposit,interest'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'debit_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'credit_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        $validated['company_id'] = $companyId;

        try {
            $transaction = $this->bankService->createManualTransaction($validated, auth()->id());

            return redirect()->route('accounting.bank.register', $validated['bank_account_id'])
                ->with('success', 'Transaction created successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
