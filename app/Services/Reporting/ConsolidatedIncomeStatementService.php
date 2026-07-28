<?php

namespace App\Services\Reporting;

use App\Models\Company;
use App\Models\Account;

class ConsolidatedIncomeStatementService
{
    public function generate(array $companyIds, string $dateFrom, string $dateTo): array
    {
        $results = [];
        $totals = ['income' => 0, 'expense' => 0];

        foreach ($companyIds as $companyId) {
            $company = Company::find($companyId);
            if (!$company) continue;

            $accounts = Account::where('company_id', $companyId)
                ->where('is_active', true)
                ->whereIn('type', ['income', 'expense'])
                ->orderBy('code')
                ->get();

            $companyData = [
                'company_name' => $company->name,
                'income' => [],
                'expense' => [],
                'net_income' => 0,
            ];

            foreach ($accounts as $account) {
                $balance = (float) $account->current_balance;
                $entry = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'sub_type' => $account->sub_type,
                    'balance' => $balance,
                ];

                $companyData[$account->type . 's'][] = $entry;
                $totals[$account->type] += $balance;
            }

            $companyData['net_income'] = $companyData['income_sum'] = array_sum(array_column($companyData['income'], 'balance'))
                - array_sum(array_column($companyData['expense'], 'balance'));

            $results[] = $companyData;
        }

        return [
            'companies' => $results,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'totals' => $totals,
            'total_net_income' => $totals['income'] - $totals['expense'],
        ];
    }
}
