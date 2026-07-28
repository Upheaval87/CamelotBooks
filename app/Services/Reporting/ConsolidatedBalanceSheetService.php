<?php

namespace App\Services\Reporting;

use App\Models\Company;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class ConsolidatedBalanceSheetService
{
    public function generate(array $companyIds, string $asOfDate): array
    {
        $results = [];
        $totals = ['assets' => 0, 'liabilities' => 0, 'equity' => 0];

        foreach ($companyIds as $companyId) {
            $company = Company::find($companyId);
            if (!$company) continue;

            $accounts = Account::where('company_id', $companyId)
                ->where('is_active', true)
                ->whereIn('type', ['asset', 'liability', 'equity'])
                ->orderBy('code')
                ->get();

            $companyData = [
                'company_name' => $company->name,
                'assets' => [],
                'liabilities' => [],
                'equity' => [],
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

            $results[] = $companyData;
        }

        return [
            'companies' => $results,
            'as_of_date' => $asOfDate,
            'totals' => $totals,
        ];
    }
}
