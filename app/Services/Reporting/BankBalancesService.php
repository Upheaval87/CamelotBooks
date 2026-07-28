<?php
namespace App\Services\Reporting;
use App\Models\Account;

class BankBalancesService
{
    public function generate(int $companyId): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('is_bank_account', [true, 1])
            ->orWhere(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->where('is_petty_cash', true)->where('is_active', true);
            })
            ->orderBy('code')->get();

        $results = [];
        $totalBalance = 0;
        foreach ($accounts as $account) {
            $balance = (float) $account->current_balance;
            $totalBalance += $balance;
            $results[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'type' => $account->is_petty_cash ? 'Petty Cash' : 'Bank',
                'balance' => $balance,
            ];
        }

        return ['accounts' => $results, 'total_balance' => $totalBalance];
    }
}
