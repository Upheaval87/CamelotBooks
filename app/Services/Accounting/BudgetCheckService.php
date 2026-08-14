<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared budget-gate service used by Purchase Requisitions and the
 * "Spend vs Budget" reporting. Reads Budget/BudgetLine per expense
 * account (no hardcoded logic).
 */
class BudgetCheckService
{
    /**
     * Evaluate a set of requisition line estimates against the approved
     * budget for the fiscal year containing $date.
     *
     * @param  array<int, array{expense_account_id: int|null, estimated_total: float|null}>  $lines
     * @return array{status: string, message: string, fiscal_year: ?string, accounts: array, total_budgeted: float, total_spent: float, total_requested: float, total_available: float}
     */
    public function check(int $companyId, array $lines, string $date): array
    {
        $fiscalYear = $this->fiscalYearFor($companyId, $date);

        $perAccount = $this->budgetByAccount($companyId, $fiscalYear);
        $spent = $this->spentByAccount($companyId, $fiscalYear);

        $requested = [];
        foreach ($lines as $line) {
            $accountId = (int) ($line['expense_account_id'] ?? 0);
            if ($accountId <= 0) {
                continue;
            }
            $requested[$accountId] = round(($requested[$accountId] ?? 0) + (float) ($line['estimated_total'] ?? 0), 2);
        }

        $accounts = collect($perAccount->keys()->merge(array_keys($requested))->unique()->sort()->values())
            ->map(function (int $accountId) use ($perAccount, $spent, $requested, $fiscalYear) {
                $account = Account::find($accountId);
                $budgeted = (float) ($perAccount->get($accountId) ?? 0);
                $spentAmount = (float) ($spent->get($accountId) ?? 0);
                $requestedAmount = (float) ($requested[$accountId] ?? 0);
                $available = round($budgeted - $spentAmount, 2);

                return [
                    'account_id' => $accountId,
                    'account_code' => $account?->code,
                    'account_name' => $account?->name ?? 'Unmapped account',
                    'budgeted' => $budgeted,
                    'spent' => $spentAmount,
                    'available' => $available,
                    'requested' => $requestedAmount,
                    'exceeded' => $available >= 0 && $requestedAmount > $available,
                ];
            })->values()->all();

        $totalBudgeted = $accounts ? collect($accounts)->sum('budgeted') : 0.0;
        $totalSpent = $accounts ? collect($accounts)->sum('spent') : 0.0;
        $totalRequested = $accounts ? collect($accounts)->sum('requested') : 0.0;
        $totalAvailable = round($totalBudgeted - $totalSpent, 2);

        $hasBudget = $totalBudgeted > 0;
        $exceeded = collect($accounts)->contains('exceeded', true);

        if (!$hasBudget) {
            $status = 'no_budget';
            $message = 'No approved budget found for these expense accounts this fiscal year.';
        } elseif ($exceeded) {
            $status = 'exceeded';
            $message = 'This requisition exceeds the remaining approved budget for one or more expense accounts.';
        } else {
            $status = 'within';
            $message = 'Within budget.';
        }

        return [
            'status' => $status,
            'message' => $message,
            'fiscal_year' => $fiscalYear?->label,
            'accounts' => $accounts,
            'total_budgeted' => $totalBudgeted,
            'total_spent' => $totalSpent,
            'total_requested' => $totalRequested,
            'total_available' => $totalAvailable,
        ];
    }

    /**
     * Spend-vs-budget aggregates across all expense accounts, used by the
     * Spend by Department/Cost Centre report header.
     *
     * @return array{accounts: array, total_budgeted: float, total_spent: float, total_available: float, date_from: string, date_to: string}
     */
    public function spendVsBudget(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? now()->startOfYear()->format('Y-m-d');
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        $fiscalYear = $this->fiscalYearFor($companyId, $dateFrom) ?? $this->fiscalYearFor($companyId, $dateTo);
        $perAccount = $this->budgetByAccount($companyId, $fiscalYear);
        $spent = $this->spentByAccount($companyId, $fiscalYear);

        $accounts = Account::where('company_id', $companyId)
            ->whereIn('type', ['expense'])
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($perAccount, $spent) {
                $budgeted = (float) ($perAccount->get($account->id) ?? 0);
                $spentAmount = (float) ($spent->get($account->id) ?? 0);

                return [
                    'account' => $account,
                    'budgeted' => $budgeted,
                    'spent' => $spentAmount,
                    'available' => round($budgeted - $spentAmount, 2),
                    'usage_pct' => $budgeted > 0 ? round($spentAmount / $budgeted * 100, 1) : null,
                ];
            })
            ->filter(fn (array $row) => $row['budgeted'] > 0 || $row['spent'] > 0)
            ->values()
            ->all();

        return [
            'accounts' => $accounts,
            'total_budgeted' => collect($accounts)->sum('budgeted'),
            'total_spent' => collect($accounts)->sum('spent'),
            'total_available' => collect($accounts)->sum('available'),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function fiscalYearFor(int $companyId, string $date): ?FiscalYear
    {
        return FiscalYear::forCompany($companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->orderByDesc('end_date')
            ->first();
    }

    private function budgetByAccount(int $companyId, ?FiscalYear $fiscalYear): Collection
    {
        if (!$fiscalYear) {
            return collect();
        }

        $budgetIds = Budget::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('status', Budget::STATUS_APPROVED)
            ->pluck('id');

        if ($budgetIds->isEmpty()) {
            return collect();
        }

        return BudgetLine::whereIn('budget_id', $budgetIds)
            ->select('account_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_id')
            ->pluck('total', 'account_id')
            ->mapWithKeys(fn ($total, $accountId) => [(int) $accountId => (float) $total]);
    }

    private function spentByAccount(int $companyId, ?FiscalYear $fiscalYear): Collection
    {
        if (!$fiscalYear) {
            return collect();
        }

        $rows = JournalEntryLine::select(
            'account_id',
            DB::raw('SUM(debit) as total_debit'),
            DB::raw('SUM(credit) as total_credit')
        )
            ->whereHas('journalEntry', function ($q) use ($companyId, $fiscalYear) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '>=', $fiscalYear->start_date->format('Y-m-d'))
                    ->where('date', '<=', $fiscalYear->end_date->format('Y-m-d'));
            })
            ->groupBy('account_id')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->whereIn('id', $rows->pluck('account_id'))
            ->get()
            ->keyBy('id');

        return $rows->mapWithKeys(function ($row) use ($accounts) {
            $account = $accounts->get($row->account_id);
            $debit = (float) $row->total_debit;
            $credit = (float) $row->total_credit;
            $amount = $account && $account->isCreditNormal()
                ? $credit - $debit
                : $debit - $credit;

            return [(int) $row->account_id => round($amount, 2)];
        });
    }
}
