<?php

namespace App\Services\BI\Concerns;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

trait BiPeriodMetrics
{
    protected const COGS_SUBTYPES = ['cost_of_goods_sold', 'cost_of_sales'];

    protected function statementTotals(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $rows = $this->glRows($companyId, $dateFrom, $dateTo, $branchId, $costCenterId);

        return $this->classifyTotals($rows);
    }

    protected function classifyTotals(\Illuminate\Support\Collection $rows): array
    {
        $revenue = 0.0;
        $cogsSub = 0.0;
        $payrollSub = 0.0;
        $depreciationSub = 0.0;
        $expenseSub = 0.0;

        foreach ($rows as $row) {
            if ($row->type === 'income') {
                $revenue += (float) $row->net;
            } else {
                $amount = (float) $row->net;
                $expenseSub += $amount;
                if (in_array($row->sub_type, self::COGS_SUBTYPES, true)) {
                    $cogsSub += $amount;
                }
                if ($this->isPayrollAccount($row->sub_type, $row->name)) {
                    $payrollSub += $amount;
                }
                if ($this->isDepreciationAccount($row->sub_type, $row->name)) {
                    $depreciationSub += $amount;
                }
            }
        }

        $cogs = $cogsSub;
        $opex = $expenseSub - $cogsSub;
        $grossProfit = $revenue - $cogs;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $revenue > 0 ? $grossProfit / $revenue * 100 : null,
            'opex' => $opex,
            'payroll' => $payrollSub,
            'depreciation' => $depreciationSub,
            'total_expenses' => $expenseSub,
            'net_income' => $revenue - $expenseSub,
            'net_margin_pct' => $revenue > 0 ? ($revenue - $expenseSub) / $revenue * 100 : null,
        ];
    }

    protected function expenseByAccount(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        $rows = $this->glRows($companyId, $dateFrom, $dateTo, $branchId, $costCenterId, true);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'account_id' => (int) $row->account_id,
                'account_code' => $row->code,
                'account_name' => $row->name,
                'sub_type' => $row->sub_type,
                'amount' => (float) $row->net,
            ];
        }

        usort($out, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return $out;
    }

    protected function cashAndBankAsOf(int $companyId, string $asOf): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('is_bank_account', true)->orWhere('is_petty_cash', true);
            })
            ->get(['id', 'code', 'name', 'opening_balance', 'is_bank_account', 'is_petty_cash', 'normal_balance']);

        $bank = 0.0;
        $cash = 0.0;
        $rows = [];

        foreach ($accounts as $account) {
            $balance = $this->balanceAsOf($account, $asOf);
            $isBank = (bool) $account->is_bank_account;
            if ($isBank) {
                $bank += $balance;
            } else {
                $cash += $balance;
            }
            $rows[] = [
                'account_id' => (int) $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'bank' => $isBank,
                'balance' => $balance,
            ];
        }

        usort($rows, fn ($a, $b) => $b['balance'] <=> $a['balance']);

        return [
            'bank' => $bank,
            'cash' => $cash,
            'total' => $bank + $cash,
            'accounts' => $rows,
        ];
    }

    protected function balanceAsOf(Account $account, string $asOf): float
    {
        $sum = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $account->company_id)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->where('journal_entries.date', '<=', $asOf)
            ->where('journal_entry_lines.account_id', $account->id)
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as dr, COALESCE(SUM(journal_entry_lines.credit), 0) as cr')
            ->first();

        $debit = (float) ($sum->dr ?? 0);
        $credit = (float) ($sum->cr ?? 0);
        $opening = (float) $account->opening_balance;

        return $account->isDebitNormal()
            ? $opening + $debit - $credit
            : $opening + $credit - $debit;
    }

    protected function glRows(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null,
        bool $withExpenseAccounts = false,
        bool $groupByBranch = false
    ): \Illuminate\Support\Collection {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('journal_entry_lines.cost_center_id', $costCenterId))
            ->when($groupByBranch, fn ($q) => $q->leftJoin('branches', 'journal_entry_lines.branch_id', '=', 'branches.id'))
            ->where(function ($q) use ($withExpenseAccounts) {
                if ($withExpenseAccounts) {
                    $q->where('accounts.type', 'expense');
                } else {
                    $q->whereIn('accounts.type', ['income', 'expense']);
                }
            })
            ->selectRaw('accounts.id as account_id, accounts.code, accounts.name, accounts.type, accounts.sub_type, '
                . 'SUM(CASE WHEN accounts.type = \'income\' THEN journal_entry_lines.credit - journal_entry_lines.debit '
                . 'ELSE journal_entry_lines.debit - journal_entry_lines.credit END) as net'
                . ($groupByBranch
                    ? ', COALESCE(journal_entry_lines.branch_id, 0) as branch_id, '
                      . 'COALESCE(branches.name, \'Unallocated\') as branch_name'
                    : ''))
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.sub_type');

        if ($groupByBranch) {
            $query->groupByRaw('COALESCE(journal_entry_lines.branch_id, 0), COALESCE(branches.name, \'Unallocated\')');
        }

        return $query->get();
    }

    protected function isPayrollAccount(?string $subType, string $name): bool
    {
        if ($subType && in_array($subType, ['payroll', 'salaries_and_wages', 'employee_benefits', 'staff_costs'], true)) {
            return true;
        }

        return (bool) preg_match('/salary|wage|payroll|benefit|staff cost|remuneration/i', $name);
    }

    protected function isDepreciationAccount(?string $subType, string $name): bool
    {
        if ($subType && in_array($subType, ['depreciation', 'depreciation_expense'], true)) {
            return true;
        }

        return (bool) preg_match('/depreciat/i', $name);
    }
}