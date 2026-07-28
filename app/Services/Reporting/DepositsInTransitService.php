<?php

namespace App\Services\Reporting;

use App\Models\DefaultAccountMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\BankTransaction;

class DepositsInTransitService
{
    public function generate(int $companyId): array
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
        if (!$undepositedAccount) {
            return [
                'lines' => [],
                'total_in_transit' => 0,
                'undeposited_account' => null,
                'account_balance' => 0,
                'variance' => 0,
            ];
        }

        $depositedJeIds = [];
        $depositTransactions = BankTransaction::where('company_id', $companyId)
            ->where('source_type', 'make_deposit')
            ->whereNotNull('reference')
            ->get();

        foreach ($depositTransactions as $tx) {
            $decoded = json_decode($tx->reference, true);
            if (is_array($decoded)) {
                $depositedJeIds = array_merge($depositedJeIds, $decoded);
            }
        }
        $depositedJeIds = array_unique($depositedJeIds);

        $query = JournalEntryLine::where('account_id', $undepositedAccount->id)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('status', JournalEntry::STATUS_POSTED);
            })
            ->where('debit', '>', 0)
            ->with('journalEntry');

        if (!empty($depositedJeIds)) {
            $query->whereNotIn('journal_entry_id', $depositedJeIds);
        }

        $outstandingLines = $query->orderBy('created_at', 'asc')->get();

        $lines = [];
        $totalInTransit = 0;

        foreach ($outstandingLines as $line) {
            $je = $line->journalEntry;
            $amount = (float) $line->debit;
            $totalInTransit += $amount;
            $lines[] = [
                'date' => $je->date ?? '',
                'reference' => $je->reference ?? '',
                'memo' => $je->memo ?? '',
                'debit' => $amount,
                'journal_entry_id' => $je->id ?? null,
            ];
        }

        $accountBalance = (float) $undepositedAccount->current_balance;

        return [
            'lines' => $lines,
            'total_in_transit' => $totalInTransit,
            'undeposited_account' => $undepositedAccount,
            'account_balance' => $accountBalance,
            'variance' => round($totalInTransit - $accountBalance, 2),
        ];
    }
}
