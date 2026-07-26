<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

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

        $balances = [];
        $totals = ['asset' => 0, 'liability' => 0, 'equity' => 0];

        foreach ($accounts as $account) {
            $balance = $this->computeBalanceAsOf($account, $companyId, $branchId, $asOfDate, $costCenterId);

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

    private function computeBalanceAsOf(Account $account, int $companyId, ?int $branchId, string $asOfDate, ?int $costCenterId = null): float
    {
        $lineQuery = JournalEntryLine::where('account_id', $account->id)
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

        $totalDebit = (float) $lineQuery->sum('debit');
        $totalCredit = (float) $lineQuery->sum('credit');

        $balance = (float) $account->opening_balance;

        if ($account->isDebitNormal()) {
            $balance += $totalDebit - $totalCredit;
        } else {
            $balance += $totalCredit - $totalDebit;
        }

        return $balance;
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
