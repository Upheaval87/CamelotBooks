<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function transfer(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        string $date,
        string $description,
        int $companyId,
        int $userId
    ): array {
        if ($fromAccountId === $toAccountId) {
            throw new InvalidArgumentException('Source and target bank accounts must be different.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Transfer amount must be greater than zero.');
        }

        $this->validateBankAccount($companyId, $fromAccountId);
        $this->validateBankAccount($companyId, $toAccountId);

        return DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $date, $description, $companyId, $userId) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $date,
                'source_module' => 'bank_transfer',
                'memo' => $description,
                'lines' => [
                    [
                        'account_id' => $toAccountId,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => $description,
                    ],
                    [
                        'account_id' => $fromAccountId,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => $description,
                    ],
                ],
            ]);

            $sourceTransaction = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $fromAccountId,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'withdrawal',
                'source_type' => 'transfer',
                'date' => $date,
                'description' => $description,
                'amount' => -$amount,
                'created_by' => $userId,
            ]);

            $targetTransaction = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $toAccountId,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'deposit',
                'source_type' => 'transfer',
                'date' => $date,
                'description' => $description,
                'amount' => $amount,
                'linked_transaction_id' => $sourceTransaction->id,
                'created_by' => $userId,
            ]);

            $sourceTransaction->update([
                'linked_transaction_id' => $targetTransaction->id,
            ]);

            return [$sourceTransaction, $targetTransaction];
        });
    }

    public function createManualTransaction(array $data, int $userId): BankTransaction
    {
        $requiredFields = ['bank_account_id', 'type', 'date', 'description', 'amount'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $type = $data['type'];
        $amount = (float) $data['amount'];

        $validTypes = ['fee', 'withdrawal', 'deposit', 'interest'];
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Invalid transaction type. Must be one of: " . implode(', ', $validTypes));
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        $this->validateBankAccount($companyId, $data['bank_account_id']);

        $isOutflow = in_array($type, ['fee', 'withdrawal']);
        $bankAmount = $isOutflow ? -$amount : $amount;

        if ($isOutflow) {
            if (empty($data['debit_account_id'])) {
                throw new InvalidArgumentException('debit_account_id is required for fees and withdrawals.');
            }
            $expenseAccount = Account::where('id', $data['debit_account_id'])
                ->where('company_id', $companyId)
                ->first();
            if (!$expenseAccount) {
                throw new InvalidArgumentException("Expense account ID {$data['debit_account_id']} not found for this company.");
            }
            $otherAccountId = $expenseAccount->id;
        } else {
            if (empty($data['credit_account_id'])) {
                throw new InvalidArgumentException('credit_account_id is required for deposits and interest.');
            }
            $incomeAccount = Account::where('id', $data['credit_account_id'])
                ->where('company_id', $companyId)
                ->first();
            if (!$incomeAccount) {
                throw new InvalidArgumentException("Income account ID {$data['credit_account_id']} not found for this company.");
            }
            $otherAccountId = $incomeAccount->id;
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $type, $amount, $bankAmount, $isOutflow, $otherAccountId) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'bank_manual',
                'reference' => $data['reference'] ?? null,
                'memo' => $data['description'],
                'lines' => [
                    [
                        'account_id' => $isOutflow ? $otherAccountId : $data['bank_account_id'],
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => $data['description'],
                    ],
                    [
                        'account_id' => $isOutflow ? $data['bank_account_id'] : $otherAccountId,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => $data['description'],
                    ],
                ],
            ]);

            $bankTransaction = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $data['bank_account_id'],
                'journal_entry_id' => $journalEntry->id,
                'type' => $type,
                'source_type' => 'manual',
                'date' => $data['date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'amount' => $bankAmount,
                'created_by' => $userId,
            ]);

            return $bankTransaction;
        });
    }

    public function getRegister(
        int $bankAccountId,
        int $companyId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): Collection {
        $query = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('company_id', $companyId)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate) {
            $query->where('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('date', '<=', $toDate);
        }

        $transactions = $query->get();
        $runningBalance = 0;

        foreach ($transactions as $transaction) {
            $runningBalance += (float) $transaction->amount;
            $transaction->running_balance = round($runningBalance, 2);
        }

        return $transactions;
    }

    public function getReconciledBalance(int $bankAccountId): float
    {
        $sum = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('is_reconciled', true)
            ->sum('amount');

        return (float) round($sum, 2);
    }

    protected function validateBankAccount(int $companyId, int $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Bank account ID {$accountId} not found or is not a bank account for this company.");
        }
    }
}
