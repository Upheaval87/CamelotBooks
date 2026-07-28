<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class TrialBalanceComparisonService
{
    public function generate(int $companyId, string $dateFrom1, string $dateTo1, string $dateFrom2, string $dateTo2): array
    {
        $period1 = $this->queryPeriod($companyId, $dateFrom1, $dateTo1);
        $period2 = $this->queryPeriod($companyId, $dateFrom2, $dateTo2);

        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        $lines = [];
        $totalDr1 = $totalCr1 = $totalDr2 = $totalCr2 = 0;

        foreach ($accounts as $account) {
            $p1 = $period1->get($account->id);
            $p2 = $period2->get($account->id);

            $dr1 = (float) ($p1->total_debit ?? 0);
            $cr1 = (float) ($p1->total_credit ?? 0);
            $dr2 = (float) ($p2->total_debit ?? 0);
            $cr2 = (float) ($p2->total_credit ?? 0);

            $net1 = $account->isCreditNormal() ? $cr1 - $dr1 : $dr1 - $cr1;
            $net2 = $account->isCreditNormal() ? $cr2 - $dr2 : $dr2 - $cr2;

            $totalDr1 += $dr1;
            $totalCr1 += $cr1;
            $totalDr2 += $dr2;
            $totalCr2 += $cr2;

            $lines[] = [
                'account' => $account,
                'debit_1' => $dr1,
                'credit_1' => $cr1,
                'debit_2' => $dr2,
                'credit_2' => $cr2,
                'variance_debit' => $dr2 - $dr1,
                'variance_credit' => $cr2 - $cr1,
            ];
        }

        return [
            'lines' => $lines,
            'total_debit_1' => $totalDr1,
            'total_credit_1' => $totalCr1,
            'total_debit_2' => $totalDr2,
            'total_credit_2' => $totalCr2,
            'date_from_1' => $dateFrom1,
            'date_to_1' => $dateTo1,
            'date_from_2' => $dateFrom2,
            'date_to_2' => $dateTo2,
        ];
    }

    private function queryPeriod(int $companyId, string $dateFrom, string $dateTo)
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
