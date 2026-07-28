<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MakeDepositService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function getUndepositedFundsLines(int $companyId): \Illuminate\Support\Collection
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');

        if (!$undepositedAccount) {
            return collect();
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
                    ->whereIn('status', ['posted']);
            })
            ->where('debit', '>', 0);

        if (!empty($depositedJeIds)) {
            $query->whereNotIn('journal_entry_id', $depositedJeIds);
        }

        return $query->with('journalEntry')->orderBy('created_at', 'asc')->get();
    }

    public function getUndepositedFundsBalance(int $companyId): float
    {
        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');

        if (!$undepositedAccount) {
            return 0;
        }

        return (float) $undepositedAccount->current_balance;
    }

    public function createDeposit(array $data, int $userId): BankTransaction
    {
        $requiredFields = ['company_id', 'bank_account_id', 'date', 'amount', 'journal_entry_ids'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_array($data[$field]) && empty($data[$field]))) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $bankAccountId = $data['bank_account_id'];
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$bankAccount) {
            throw new InvalidArgumentException("Bank account ID {$bankAccountId} not found or is not a bank account.");
        }

        $undepositedAccount = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');

        if (!$undepositedAccount) {
            throw new InvalidArgumentException('Undeposited Funds account not found.');
        }

        $selectedJEs = JournalEntryLine::whereIn('journal_entry_id', $data['journal_entry_ids'])
            ->where('account_id', $undepositedAccount->id)
            ->where('debit', '>', 0)
            ->get();

        $totalSelected = $selectedJEs->sum('debit');

        if (round($totalSelected, 2) !== round($amount, 2)) {
            throw new InvalidArgumentException(
                "Selected amount (" . number_format($totalSelected, 2) .
                ") does not match deposit amount (" . number_format($amount, 2) . ")."
            );
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $bankAccountId, $amount, $bankAccount, $undepositedAccount) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'make_deposit',
                'reference' => $data['reference'] ?? null,
                'memo' => $data['description'] ?? "Deposit to {$bankAccount->name}",
                'lines' => [
                    [
                        'account_id' => $bankAccountId,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => $data['description'] ?? "Deposit to {$bankAccount->name}",
                    ],
                    [
                        'account_id' => $undepositedAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => $data['description'] ?? "Deposit to {$bankAccount->name}",
                    ],
                ],
            ]);

            $bankTransaction = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'deposit',
                'source_type' => 'make_deposit',
                'source_id' => $journalEntry->id,
                'date' => $data['date'],
                'description' => $data['description'] ?? "Deposit to {$bankAccount->name}",
                'reference' => json_encode($data['journal_entry_ids']),
                'amount' => $amount,
                'created_by' => $userId,
            ]);

            return $bankTransaction;
        });
    }
}
