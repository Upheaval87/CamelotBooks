<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\PettyCashService;
use App\Services\Accounting\BankService;

class CashPositionController extends Controller
{
    public function __construct(
        protected PettyCashService $pettyCashService,
        protected BankService $bankService
    ) {
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $account->reconciled_balance = $this->bankService->getReconciledBalance($account->id);
                return $account;
            });

        $pettyCashSummary = $this->pettyCashService->getFundSummary($companyId);

        $totalBankBalance = $bankAccounts->sum('current_balance');
        $totalPettyCash = collect($pettyCashSummary)->sum('current_balance');
        $totalCashPosition = $totalBankBalance + $totalPettyCash;

        return view('accounting.cash-position.index', compact(
            'bankAccounts',
            'pettyCashSummary',
            'totalBankBalance',
            'totalPettyCash',
            'totalCashPosition'
        ));
    }
}
