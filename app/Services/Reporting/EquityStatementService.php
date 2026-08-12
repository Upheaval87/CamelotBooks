<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class EquityStatementService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $company = Company::find($companyId);
        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->where('type', 'equity')
            ->orderBy('code')
            ->get();

        $openingBalances = [];
        $closingBalances = [];
        $movements = [];

        foreach ($accounts as $account) {
            $openingBalance = $this->computeBalanceAsOf($account, $companyId, $branchId, $dateFrom);
            $closingBalance = $this->computeBalanceAsOf($account, $companyId, $branchId, $dateTo);
            $periodMovement = $closingBalance - $openingBalance;

            $openingBalances[] = [
                'account' => $account,
                'balance' => $openingBalance,
            ];
            $closingBalances[] = [
                'account' => $account,
                'balance' => $closingBalance,
            ];
            $movements[] = [
                'account' => $account,
                'opening' => $openingBalance,
                'closing' => $closingBalance,
                'movement' => $periodMovement,
            ];
        }

        $netIncome = $this->computeNetIncome($companyId, $branchId, $dateFrom, $dateTo);

        $totalOpening = collect($openingBalances)->sum('balance');
        $totalClosing = collect($closingBalances)->sum('balance');

        return [
            'company' => $company,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'movements' => $movements,
            'net_income' => $netIncome,
            'total_opening' => $totalOpening,
            'total_closing' => $totalClosing,
            'branch_id' => $branchId,
        ];
    }

    private function computeBalanceAsOf(Account $account, int $companyId, ?int $branchId, string $asOfDate): float
    {
        $query = DB::table('journal_entry_lines AS jel')
            ->join('journal_entries AS je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_id', $account->id)
            ->where('je.company_id', $companyId)
            ->where('je.status', 'posted')
            ->where('je.date', '<=', $asOfDate);

        if ($branchId) {
            $query->where('jel.branch_id', $branchId);
        }

        $result = $query->selectRaw('COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0) AS balance')
            ->first();

        $balance = (float) ($result->balance ?? 0);

        $normalBalance = in_array($account->type, ['asset', 'expense']) ? 1 : -1;
        return $balance * $normalBalance;
    }

    private function computeNetIncome(int $companyId, ?int $branchId, string $dateFrom, string $dateTo): float
    {
        $query = DB::table('journal_entry_lines AS jel')
            ->join('journal_entries AS je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts AS a', 'a.id', '=', 'jel.account_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', 'posted')
            ->where('je.date', '>=', $dateFrom)
            ->where('je.date', '<=', $dateTo)
            ->whereIn('a.type', ['income', 'expense']);

        if ($branchId) {
            $query->where('jel.branch_id', $branchId);
        }

        $result = $query->selectRaw("
            COALESCE(SUM(CASE WHEN a.type = 'income' THEN jel.credit - jel.debit ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN a.type = 'expense' THEN jel.debit - jel.credit ELSE 0 END), 0) AS net_income
        ")->first();

        return (float) ($result->net_income ?? 0);
    }
}
