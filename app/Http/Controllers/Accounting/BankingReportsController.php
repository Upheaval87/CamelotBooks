<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;

class BankingReportsController extends Controller
{
    public function index()
    {
        $user = request()->user();

        $reports = collect([
            [
                'key' => 'bank_balances',
                'name' => 'Bank Balances',
                'description' => 'Current balance, available funds and reconciled position of every bank account.',
                'route' => 'accounting.reports.bank-balances',
                'permission' => 'reports.bank_balances.view',
            ],
            [
                'key' => 'deposits_in_transit',
                'name' => 'Deposits in Transit',
                'description' => 'Receipts captured but not yet deposited to a bank account.',
                'route' => 'accounting.reports.deposits-in-transit',
                'permission' => 'reports.deposits_in_transit.view',
            ],
            [
                'key' => 'cheque_register',
                'name' => 'Cheque Register',
                'description' => 'All cheques written across your bank accounts with their current status.',
                'route' => 'accounting.reports.cheque-register',
                'permission' => 'reports.cheque_register.view',
            ],
            [
                'key' => 'cash_position',
                'name' => 'Cash Position',
                'description' => 'Period-by-period opening, receipts, payments and closing cash balances.',
                'route' => 'accounting.cash-position.index',
                'permission' => 'reports.cash_position.view',
            ],
        ])->filter(fn ($r) => $user->can($r['permission']))
            ->values();

        return view('accounting.banking.reports', compact('reports'));
    }
}
