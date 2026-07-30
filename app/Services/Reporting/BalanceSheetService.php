<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceSheetService
{
    private IncomeStatementService $incomeStatementService;

    public function __construct(IncomeStatementService $incomeStatementService)
    {
        $this->incomeStatementService = $incomeStatementService;
    }

    public function generate(int $companyId, ?int $branchId, string $asOfDate, ?int $costCenterId = null): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get();

        // Single grouped query: 1 query instead of N*2
        $lineQuery = JournalEntryLine::select('account_id',
                DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit'))
            ->whereHas('journalEntry', function ($q) use ($companyId, $asOfDate) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '<=', $asOfDate);
            });

        if ($branchId) {
            $lineQuery->where('branch_id', $branchId);
        }

        if ($costCenterId) {
            $lineQuery->where('cost_center_id', $costCenterId);
        }

        $lineTotals = $lineQuery->groupBy('account_id')->get()->keyBy('account_id');

        $balances = [];
        $totals = ['asset' => 0, 'liability' => 0, 'equity' => 0];

        foreach ($accounts as $account) {
            $totalsData = $lineTotals->get($account->id);
            $totalDebit = $totalsData ? (float) $totalsData->total_debit : 0;
            $totalCredit = $totalsData ? (float) $totalsData->total_credit : 0;

            $balance = (float) $account->opening_balance;

            if ($account->isDebitNormal()) {
                $balance += $totalDebit - $totalCredit;
            } else {
                $balance += $totalCredit - $totalDebit;
            }

            if (abs($balance) < 0.001) {
                continue;
            }

            $balances[] = [
                'account' => $account,
                'balance' => $balance,
            ];

            $totals[$account->type] += $balance;
        }

        $currentYearEarnings = $this->computeCurrentYearEarnings($companyId, $branchId, $asOfDate, $costCenterId);

        $grouped = $this->groupBySubType($balances);

        $totalLiabilitiesEquity = $totals['liability'] + $totals['equity'] + $currentYearEarnings;

        return [
            'groups' => $grouped,
            'total_assets' => $totals['asset'],
            'total_liabilities' => $totals['liability'],
            'total_equity' => $totals['equity'] + $currentYearEarnings,
            'current_year_earnings' => $currentYearEarnings,
            'as_of_date' => $asOfDate,
            'balanced' => abs($totals['asset'] - $totalLiabilitiesEquity) < 0.01,
        ];
    }

    private function computeCurrentYearEarnings(int $companyId, ?int $branchId, string $asOfDate, ?int $costCenterId = null): float
    {
        $company = Company::findOrFail($companyId);
        $fyStartMonth = $company->fiscal_year_start_month ?? 1;

        $asOf = \Carbon\Carbon::parse($asOfDate);
        $fyYear = $asOf->month >= $fyStartMonth ? $asOf->year : $asOf->year - 1;
        $fyStart = \Carbon\Carbon::create($fyYear, $fyStartMonth, 1)->toDateString();

        $closedFy = FiscalYear::where('company_id', $companyId)
            ->where('status', 'closed')
            ->where('start_date', '<=', $asOfDate)
            ->where('end_date', '>=', $fyStart)
            ->first();

        if ($closedFy) {
            return 0.0;
        }

        return $this->incomeStatementService->computeNetIncome($companyId, $branchId, $fyStart, $asOfDate, $costCenterId);
    }

    private function groupBySubType(array $balances): array
    {
        $grouped = [
            'asset' => [
                'current_asset' => [],
                'non_current_asset' => [],
                'other_asset' => [],
            ],
            'liability' => [
                'current_liability' => [],
                'non_current_liability' => [],
                'other_liability' => [],
            ],
            'equity' => [
                'equity' => [],
            ],
        ];

        foreach ($balances as $item) {
            $type = $item['account']->type;
            $subType = $item['account']->sub_type;

            if (!isset($grouped[$type][$subType])) {
                $grouped[$type][$subType] = [];
            }

            $grouped[$type][$subType][] = $item;
        }

        return $grouped;
    }
}
