<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PettyCashService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function createFund(array $data, int $userId): Account
    {
        $companyId = $data['company_id'];

        $existing = Account::where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->where('name', $data['name'])
            ->first();

        if ($existing) {
            throw new InvalidArgumentException("A petty cash fund with name '{$data['name']}' already exists.");
        }

        $account = Account::create([
            'company_id' => $companyId,
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_petty_cash' => true,
            'is_active' => true,
            'petty_cash_float' => 0,
        ]);

        return $account;
    }

    public function establishFund(Account $pettyCashAccount, int $bankAccountId, float $amount, string $date, int $userId): BankTransaction
    {
        if (!$pettyCashAccount->is_petty_cash) {
            throw new InvalidArgumentException('Account is not a petty cash fund.');
        }

        $companyId = $pettyCashAccount->company_id;

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$bankAccount) {
            throw new InvalidArgumentException('Bank account not found.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($pettyCashAccount, $bankAccount, $amount, $date, $userId, $companyId) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $date,
                'source_module' => 'petty_cash_establish',
                'memo' => "Establish petty cash fund - {$pettyCashAccount->name}",
                'lines' => [
                    [
                        'account_id' => $pettyCashAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "Establish petty cash fund",
                    ],
                    [
                        'account_id' => $bankAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Establish petty cash fund",
                    ],
                ],
            ]);

            $bankTx = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccount->id,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'withdrawal',
                'source_type' => 'petty_cash',
                'source_id' => $pettyCashAccount->id,
                'date' => $date,
                'description' => "Establish petty cash - {$pettyCashAccount->name}",
                'amount' => -$amount,
                'created_by' => $userId,
            ]);

            $pettyCashAccount->update([
                'petty_cash_float' => $amount,
            ]);

            return $bankTx;
        });
    }

    public function recordExpense(array $data, int $userId): array
    {
        $requiredFields = ['company_id', 'petty_cash_account_id', 'debit_account_id', 'date', 'amount', 'description'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $pettyCashAccount = Account::where('id', $data['petty_cash_account_id'])
            ->where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->first();

        if (!$pettyCashAccount) {
            throw new InvalidArgumentException('Petty cash fund not found.');
        }

        $debitAccount = Account::where('id', $data['debit_account_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$debitAccount) {
            throw new InvalidArgumentException('Expense account not found.');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $pettyCashAccount, $debitAccount, $amount) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'petty_cash_expense',
                'memo' => $data['description'],
                'lines' => [
                    [
                        'account_id' => $debitAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => $data['description'],
                    ],
                    [
                        'account_id' => $pettyCashAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => $data['description'],
                    ],
                ],
            ]);

            $pettyCashAccount->refresh();
            $newBalance = (float) $pettyCashAccount->current_balance;
            $pettyCashAccount->update([
                'petty_cash_float' => max(0, $newBalance),
            ]);

            return [
                'journal_entry' => $journalEntry,
                'new_balance' => $newBalance,
            ];
        });
    }

    public function replenishFund(array $data, int $userId): BankTransaction
    {
        $requiredFields = ['company_id', 'petty_cash_account_id', 'bank_account_id', 'date', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $pettyCashAccount = Account::where('id', $data['petty_cash_account_id'])
            ->where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->first();

        if (!$pettyCashAccount) {
            throw new InvalidArgumentException('Petty cash fund not found.');
        }

        $bankAccount = Account::where('id', $data['bank_account_id'])
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$bankAccount) {
            throw new InvalidArgumentException('Bank account not found.');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $pettyCashAccount, $bankAccount, $amount) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'petty_cash_replenish',
                'memo' => $data['description'] ?? "Replenish petty cash - {$pettyCashAccount->name}",
                'lines' => [
                    [
                        'account_id' => $pettyCashAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => $data['description'] ?? "Replenish petty cash",
                    ],
                    [
                        'account_id' => $bankAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => $data['description'] ?? "Replenish petty cash",
                    ],
                ],
            ]);

            $bankTx = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccount->id,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'withdrawal',
                'source_type' => 'petty_cash',
                'source_id' => $pettyCashAccount->id,
                'date' => $data['date'],
                'description' => $data['description'] ?? "Replenish petty cash - {$pettyCashAccount->name}",
                'amount' => -$amount,
                'created_by' => $userId,
            ]);

            $pettyCashAccount->refresh();
            $newFloat = (float) $pettyCashAccount->current_balance;
            $pettyCashAccount->update([
                'petty_cash_float' => $newFloat,
            ]);

            return $bankTx;
        });
    }

    public function getFundSummary(int $companyId): array
    {
        $funds = Account::where('company_id', $companyId)
            ->where('is_petty_cash', true)
            ->get();

        $summary = [];
        foreach ($funds as $fund) {
            $summary[] = [
                'id' => $fund->id,
                'name' => $fund->name,
                'code' => $fund->code,
                'float' => (float) $fund->petty_cash_float,
                'current_balance' => (float) $fund->current_balance,
                'spent' => (float) $fund->petty_cash_float - (float) $fund->current_balance,
            ];
        }

        return $summary;
    }

    public function getExpenses(int $companyId, int $pettyCashAccountId, ?string $fromDate = null, ?string $toDate = null): \Illuminate\Support\Collection
    {
        $query = \App\Models\JournalEntryLine::where('account_id', $pettyCashAccountId)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('source_module', 'petty_cash_expense')
                    ->whereIn('status', ['posted']);
            })
            ->where('credit', '>', 0)
            ->with('journalEntry');

        if ($fromDate) {
            $query->whereHas('journalEntry', function ($q) use ($fromDate) {
                $q->where('date', '>=', $fromDate);
            });
        }

        if ($toDate) {
            $query->whereHas('journalEntry', function ($q) use ($toDate) {
                $q->where('date', '<=', $toDate);
            });
        }

        return $query->orderBy('created_at', 'asc')->get();
    }
}
