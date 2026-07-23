<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Budget;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class BudgetVarianceService
{
    public function generateVarianceReport(Budget $budget): array
    {
        $companyId = $budget->company_id;
        $fiscalYear = $budget->fiscalYear;

        $dateFrom = $fiscalYear->start_date->format('Y-m-d');
        $dateTo = $fiscalYear->end_date->format('Y-m-d');

        $actualLines = $this->queryPeriodLines($companyId, $dateFrom, $dateTo);

        $budgetLines = $budget->lines()->with('account')->get();

        $accounts = Account::where('company_id', $companyId)
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get();

        $result = [];
        $totalBudget = 0;
        $totalActual = 0;

        foreach ($accounts as $account) {
            $budgetAmount = (float) $budgetLines
                ->where('account_id', $account->id)
                ->sum('amount');

            $line = $actualLines->get($account->id);
            $debit = (float) ($line->total_debit ?? 0);
            $credit = (float) ($line->total_credit ?? 0);

            if ($account->isCreditNormal()) {
                $actualAmount = $credit - $debit;
            } else {
                $actualAmount = $debit - $credit;
            }

            $variance = $budgetAmount - $actualAmount;

            $result[] = [
                'account' => $account,
                'budget' => $budgetAmount,
                'actual' => $actualAmount,
                'variance' => $variance,
                'variance_pct' => $budgetAmount != 0 ? round(($variance / $budgetAmount) * 100, 1) : null,
            ];

            $totalBudget += $budgetAmount;
            $totalActual += $actualAmount;
        }

        return [
            'budget' => $budget,
            'lines' => $result,
            'total_budget' => $totalBudget,
            'total_actual' => $totalActual,
            'total_variance' => $totalBudget - $totalActual,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function queryPeriodLines(int $companyId, string $dateFrom, string $dateTo)
    {
        return JournalEntryLine::select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->whereHas('journalEntry', function ($q) use ($companyId, $dateFrom, $dateTo) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '>=', $dateFrom)
                    ->where('date', '<=', $dateTo);
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');
    }
}
