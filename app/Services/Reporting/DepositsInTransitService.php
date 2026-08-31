<?php

namespace App\Services\Reporting;

use App\Models\DefaultAccountMapping;
use App\Models\BankDeposit;
use App\Models\BankDepositLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

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

        // Durable exclusion: skip 1050 lines already claimed by a non-void deposit.
        $claimedLineIds = BankDepositLine::whereExists(function ($q) {
            $q->selectRaw(1)
                ->from('bank_deposits')
                ->whereColumn('bank_deposits.id', 'bank_deposit_lines.deposit_id')
                ->where('bank_deposits.status', '!=', BankDeposit::STATUS_VOID);
        })->pluck('source_id');

        $query = JournalEntryLine::where('account_id', $undepositedAccount->id)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('status', JournalEntry::STATUS_POSTED);
            })
            ->where('debit', '>', 0)
            ->with('journalEntry');

        if ($claimedLineIds->isNotEmpty()) {
            $query->whereNotIn('id', $claimedLineIds);
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
