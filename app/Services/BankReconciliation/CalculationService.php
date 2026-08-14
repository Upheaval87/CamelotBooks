<?php

namespace App\Services\BankReconciliation;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Reconciliation;
use App\Models\ReconciliationAdjustment;

class CalculationService
{
    /**
     * Recompute and persist the reconciliation's statement balance, book balance
     * and difference per the §5.4 pattern:
     *
     *   Adjusted bank balance = statement closing + bank-side adjustments
     *   Book balance          = GL balance of the account as of period end
     *   Adjusted book balance = book balance + book-side adjustments
     *   Difference            = adjusted bank − adjusted book  (must be 0)
     */
    public function recalculate(Reconciliation $reconciliation): Reconciliation
    {
        $bankAdjustments = $reconciliation->adjustments()
            ->where('side', ReconciliationAdjustment::SIDE_BANK)
            ->where('status', '!=', ReconciliationAdjustment::STATUS_REVERSED)
            ->get();

        $bookAdjustments = $reconciliation->adjustments()
            ->where('side', ReconciliationAdjustment::SIDE_BOOK)
            ->where('status', '!=', ReconciliationAdjustment::STATUS_REVERSED)
            ->get();

        $bankAdjustmentTotal = $this->signedTotal($bankAdjustments);
        $bookAdjustmentTotal = $this->signedTotal($bookAdjustments);

        $statementBalance = (float) $reconciliation->closing_balance + $bankAdjustmentTotal;
        $bookBalance = $this->bookBalance($reconciliation) + $bookAdjustmentTotal;
        $difference = round($statementBalance - $bookBalance, 2);

        $reconciliation->statement_balance = $statementBalance;
        $reconciliation->book_balance = $bookBalance;
        $reconciliation->difference = $difference;
        $reconciliation->save();

        return $reconciliation->fresh();
    }

    /**
     * The general-ledger balance of the bank account as of the period end:
     * opening balance plus the posted/reversed journal activity on that account.
     */
    public function bookBalance(Reconciliation $reconciliation): float
    {
        $account = $reconciliation->bankAccount;
        if (!$account) {
            return 0.0;
        }

        $periodEnd = $reconciliation->period_end ?? $reconciliation->statement_date;
        if (!$periodEnd) {
            return 0.0;
        }

        $query = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($reconciliation, $periodEnd) {
                $q->where('company_id', $reconciliation->company_id)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '<=', $periodEnd);
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $totalDebit = (float) $query->total_debit;
        $totalCredit = (float) $query->total_credit;

        $balance = $account->isDebitNormal()
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;

        return round($balance + (float) $account->opening_balance, 2);
    }

    /**
     * The account balance at the period START — used to validate the statement
     * opening balance the user typed.
     */
    public function bookOpeningBalance(Reconciliation $reconciliation): float
    {
        $account = $reconciliation->bankAccount;
        if (!$account) {
            return 0.0;
        }

        $periodStart = $reconciliation->period_start;
        if (!$periodStart) {
            return 0.0;
        }

        $prior = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($reconciliation, $periodStart) {
                $q->where('company_id', $reconciliation->company_id)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '<', $periodStart);
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $totalDebit = (float) $prior->total_debit;
        $totalCredit = (float) $prior->total_credit;

        $balance = $account->isDebitNormal()
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;

        return round($balance + (float) $account->opening_balance, 2);
    }

    public function signedTotal(iterable $adjustments): float
    {
        $total = 0.0;

        foreach ($adjustments as $adjustment) {
            $total += $this->signedAmount($adjustment);
        }

        return round($total, 2);
    }

    public function signedAmount(ReconciliationAdjustment $adjustment): float
    {
        $amount = (float) $adjustment->amount;
        if ($adjustment->sign === ReconciliationAdjustment::SIGN_SUBTRACT) {
            $amount = -$amount;
        }

        return $amount;
    }
}
