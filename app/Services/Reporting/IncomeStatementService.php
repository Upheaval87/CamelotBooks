<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomeStatementService
{
    public function generate(
        int $companyId,
        ?int $branchId,
        string $dateFrom,
        string $dateTo,
        ?string $compareMode = null,
        ?int $costCenterId = null
    ): array {
        $lines = $this->queryPeriodLines($companyId, $branchId, $dateFrom, $dateTo, $costCenterId);
        $accounts = $this->getIncomeExpenseAccounts($companyId);

        $result = $this->buildStatement($lines, $accounts);

        $result['date_from'] = $dateFrom;
        $result['date_to'] = $dateTo;
        $result['net_income'] = $result['total_income'] - $result['total_expenses'];

        if ($compareMode) {
            $result['comparison'] = $this->getComparison($companyId, $branchId, $dateFrom, $dateTo, $compareMode, $costCenterId);
        }

        return $result;
    }

    public function computeNetIncome(int $companyId, ?int $branchId, string $dateFrom, string $dateTo, ?int $costCenterId = null): float
    {
        $income = (float) JournalEntryLine::whereHas('journalEntry', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->where('company_id', $companyId)
                ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo);
        })
            ->whereHas('account', fn ($q) => $q->where('type', 'income'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId))
            ->sum(DB::raw('credit - debit'));

        $expenses = (float) JournalEntryLine::whereHas('journalEntry', function ($q) use ($companyId, $dateFrom, $dateTo) {
            $q->where('company_id', $companyId)
                ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                ->where('date', '>=', $dateFrom)
                ->where('date', '<=', $dateTo);
        })
            ->whereHas('account', fn ($q) => $q->where('type', 'expense'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId))
            ->sum(DB::raw('debit - credit'));

        return $income - $expenses;
    }

    private function queryPeriodLines(int $companyId, ?int $branchId, string $dateFrom, string $dateTo, ?int $costCenterId = null): Collection
    {
        return JournalEntryLine::select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->whereHas('journalEntry', function ($q) use ($companyId, $dateFrom, $dateTo) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '>=', $dateFrom)
                    ->where('date', '<=', $dateTo);
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');
    }

    private function getIncomeExpenseAccounts(int $companyId): Collection
    {
        return Account::where('company_id', $companyId)
            ->active()
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get();
    }

    private function buildStatement(Collection $lines, Collection $accounts): array
    {
        $grouped = [
            'income' => [],
            'expense' => [],
        ];

        foreach ($accounts as $account) {
            $line = $lines->get($account->id);
            $debit = (float) ($line->total_debit ?? 0);
            $credit = (float) ($line->total_credit ?? 0);

            if ($account->isCreditNormal()) {
                $net = $credit - $debit;
            } else {
                $net = $debit - $credit;
            }

            $grouped[$account->type][$account->sub_type][] = [
                'account' => $account,
                'net' => $net,
            ];
        }

        $totalIncome = 0;
        $totalExpenses = 0;

        foreach ($grouped['income'] as $subType => $items) {
            foreach ($items as &$item) {
                $totalIncome += $item['net'];
            }
        }

        foreach ($grouped['expense'] as $subType => $items) {
            foreach ($items as &$item) {
                $totalExpenses += $item['net'];
            }
        }

        return [
            'groups' => $grouped,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
        ];
    }

    private function getComparison(int $companyId, ?int $branchId, string $dateFrom, string $dateTo, string $mode, ?int $costCenterId = null): array
    {
        $start = \Carbon\Carbon::parse($dateFrom);
        $end = \Carbon\Carbon::parse($dateTo);
        $length = $start->diffInDays($end) + 1;

        if ($mode === 'prior_period') {
            $compTo = $start->copy()->subDay();
            $compFrom = $compTo->copy()->subDays($length - 1);
        } elseif ($mode === 'year_ago') {
            $compFrom = $start->copy()->subYear();
            $compTo = $end->copy()->subYear();
        } else {
            return [];
        }

        $lines = $this->queryPeriodLines($companyId, $branchId, $compFrom->toDateString(), $compTo->toDateString(), $costCenterId);
        $accounts = $this->getIncomeExpenseAccounts($companyId);
        $result = $this->buildStatement($lines, $accounts);

        $result['date_from'] = $compFrom->toDateString();
        $result['date_to'] = $compTo->toDateString();
        $result['net_income'] = $result['total_income'] - $result['total_expenses'];

        return $result;
    }
}
