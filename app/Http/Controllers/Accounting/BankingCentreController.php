<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Cheque;
use App\Models\DefaultAccountMapping;

class BankingCentreController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        $pettyFunds = Account::where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->orderBy('code')
            ->get();

        $bankBalance = round($bankAccounts->sum(fn ($a) => (float) $a->current_balance), 2);
        $pettyBalance = round($pettyFunds->sum(fn ($a) => (float) $a->current_balance), 2);

        $outstandingCheques = Cheque::where('company_id', $companyId)
            ->where('status', Cheque::STATUS_OUTSTANDING)
            ->get();

        $outstandingTotal = round($outstandingCheques->sum('amount'), 2);

        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
        $undepositedBalance = $undepositedAccount ? (float) $undepositedAccount->current_balance : 0.0;

        $recentTransactions = BankTransaction::where('company_id', $companyId)
            ->with('bankAccount', 'journalEntry')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('accounting.banking.index', compact(
            'companyId',
            'bankAccounts',
            'pettyFunds',
            'bankBalance',
            'pettyBalance',
            'outstandingCheques',
            'outstandingTotal',
            'undepositedBalance',
            'recentTransactions'
        ));
    }
}
