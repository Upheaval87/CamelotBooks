<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class CashFlowStatementService
{
    private IncomeStatementService $incomeStatementService;

    public function __construct(IncomeStatementService $incomeStatementService)
    {
        $this->incomeStatementService = $incomeStatementService;
    }

    public function generate(int $companyId, ?int $branchId, string $dateFrom, string $dateTo): array
    {
        $netIncome = $this->incomeStatementService->computeNetIncome($companyId, $branchId, $dateFrom, $dateTo);

        $nonCashExpenses = $this->getNonCashExpenses($companyId, $branchId, $dateFrom, $dateTo);

        $balanceSheetAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->whereNotNull('cash_flow_section')
            ->orderBy('code')
            ->get();

        $sections = ['operating' => [], 'investing' => [], 'financing' => []];

        foreach ($balanceSheetAccounts as $account) {
            $balanceStart = $this->getBalanceAsOf($account, $companyId, $branchId, \Carbon\Carbon::parse($dateFrom)->subDay()->toDateString());
            $balanceEnd = $this->getBalanceAsOf($account, $companyId, $branchId, $dateTo);

            $change = $balanceEnd - $balanceStart;

            if (abs($change) < 0.001) {
                continue;
            }

            $isLiabilityOrEquity = in_array($account->type, ['liability', 'equity']);

            if ($isLiabilityOrEquity) {
                $cashEffect = $change;
            } else {
                $cashEffect = -$change;
            }

            $sections[$account->cash_flow_section][] = [
                'account' => $account,
                'balance_start' => $balanceStart,
                'balance_end' => $balanceEnd,
                'change' => $change,
                'cash_effect' => $cashEffect,
            ];
        }

        $operatingTotal = $netIncome + $nonCashExpenses['total'];
        foreach ($sections['operating'] as $item) {
            $operatingTotal += $item['cash_effect'];
        }

        $investingTotal = 0;
        foreach ($sections['investing'] as $item) {
            $investingTotal += $item['cash_effect'];
        }

        $financingTotal = 0;
        foreach ($sections['financing'] as $item) {
            $financingTotal += $item['cash_effect'];
        }

        $netChange = $operatingTotal + $investingTotal + $financingTotal;

        $beginningCash = $this->getCashBalanceAsOf($companyId, $branchId, \Carbon\Carbon::parse($dateFrom)->subDay()->toDateString());
        $endingCash = $beginningCash + $netChange;

        $actualEndingCash = $this->getCashBalanceAsOf($companyId, $branchId, $dateTo);

        $mismatch = abs($endingCash - $actualEndingCash) > 0.01 ? $actualEndingCash - $endingCash : null;

        return [
            'net_income' => $netIncome,
            'non_cash_expenses' => $nonCashExpenses,
            'sections' => $sections,
            'operating_total' => $operatingTotal,
            'investing_total' => $investingTotal,
            'financing_total' => $financingTotal,
            'net_change' => $netChange,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
            'actual_ending_cash' => $actualEndingCash,
            'mismatch' => $mismatch,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function getNonCashExpenses(int $companyId, ?int $branchId, string $dateFrom, string $dateTo): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->where('is_non_cash', true)
            ->get();

        $items = [];
        $total = 0;

        foreach ($accounts as $account) {
            $lineQuery = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($companyId, $dateFrom, $dateTo) {
                    $q->where('company_id', $companyId)
                        ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                        ->where('date', '>=', $dateFrom)
                        ->where('date', '<=', $dateTo);
                });

            if ($branchId) {
                $lineQuery->where('branch_id', $branchId);
            }

            $debit = (float) $lineQuery->sum('debit');
            $credit = (float) $lineQuery->sum('credit');

            $net = $account->isDebitNormal() ? ($debit - $credit) : ($credit - $debit);

            if (abs($net) > 0.001) {
                $items[] = [
                    'account' => $account,
                    'amount' => $net,
                ];
                $total += $net;
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    private function getBalanceAsOf(Account $account, int $companyId, ?int $branchId, string $asOfDate): float
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

    private function getCashBalanceAsOf(int $companyId, ?int $branchId, string $asOfDate): float
    {
        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->where('is_bank_account', true)
            ->get();

        $total = 0;

        foreach ($accounts as $account) {
            $total += $this->getBalanceAsOf($account, $companyId, $branchId, $asOfDate);
        }

        return $total;
    }
}
