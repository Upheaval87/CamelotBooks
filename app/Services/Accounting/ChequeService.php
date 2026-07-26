<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Cheque;
use App\Models\NumberingSequence;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChequeService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function writeCheque(array $data, int $userId): Cheque
    {
        $requiredFields = ['company_id', 'bank_account_id', 'date', 'payee', 'amount', 'debit_account_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $bankAccountId = $data['bank_account_id'];
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new InvalidArgumentException('Cheque amount must be greater than zero.');
        }

        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$bankAccount) {
            throw new InvalidArgumentException("Bank account ID {$bankAccountId} is not a valid bank account.");
        }

        $debitAccount = Account::where('id', $data['debit_account_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$debitAccount) {
            throw new InvalidArgumentException("Debit account ID {$data['debit_account_id']} not found.");
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $bankAccountId, $amount, $bankAccount, $debitAccount) {
            $chequeNumber = $this->getNextChequeNumber($companyId, $bankAccountId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['date'],
                'source_module' => 'cheque',
                'reference' => "CHQ-" . str_pad($chequeNumber, 6, '0', STR_PAD_LEFT),
                'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}",
                'lines' => [
                    [
                        'account_id' => $debitAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}",
                    ],
                    [
                        'account_id' => $bankAccountId,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => $data['memo'] ?? "Cheque #{$chequeNumber} to {$data['payee']}",
                    ],
                ],
            ]);

            $bankTx = BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'type' => 'withdrawal',
                'source_type' => 'cheque',
                'source_id' => $journalEntry->id,
                'date' => $data['date'],
                'description' => "Cheque #{$chequeNumber} to {$data['payee']}",
                'reference' => "CHQ-" . str_pad($chequeNumber, 6, '0', STR_PAD_LEFT),
                'amount' => -$amount,
                'created_by' => $userId,
            ]);

            $cheque = Cheque::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'cheque_number' => $chequeNumber,
                'date' => $data['date'],
                'payee' => $data['payee'],
                'memo' => $data['memo'] ?? null,
                'amount' => $amount,
                'status' => Cheque::STATUS_OUTSTANDING,
                'source_type' => 'bank_transaction',
                'source_id' => $bankTx->id,
                'journal_entry_id' => $journalEntry->id,
                'created_by' => $userId,
            ]);

            return $cheque;
        });
    }

    public function voidCheque(Cheque $cheque, int $userId): Cheque
    {
        if ($cheque->status === Cheque::STATUS_VOID) {
            throw new InvalidArgumentException('This cheque is already void.');
        }

        $companyId = $cheque->company_id;

        return DB::transaction(function () use ($cheque, $userId, $companyId) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => now()->format('Y-m-d'),
                'source_module' => 'cheque_void',
                'reference' => "VOID-CHQ-" . str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT),
                'memo' => "Void of cheque #{$cheque->cheque_number}",
                'lines' => [
                    [
                        'account_id' => $cheque->bank_account_id,
                        'debit' => $cheque->amount,
                        'credit' => 0,
                        'memo' => "Void of cheque #{$cheque->cheque_number}",
                    ],
                    [
                        'account_id' => $this->getDebitAccountForCheque($cheque)->id,
                        'debit' => 0,
                        'credit' => $cheque->amount,
                        'memo' => "Void of cheque #{$cheque->cheque_number}",
                    ],
                ],
            ]);

            $cheque->update([
                'status' => Cheque::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $userId,
            ]);

            if ($cheque->source_type === 'bank_transaction' && $cheque->source_id) {
                BankTransaction::where('id', $cheque->source_id)->delete();
            }

            return $cheque;
        });
    }

    public function markCleared(Cheque $cheque, int $userId): Cheque
    {
        if ($cheque->status !== Cheque::STATUS_OUTSTANDING) {
            throw new InvalidArgumentException('Only outstanding cheques can be marked as cleared.');
        }

        $cheque->update(['status' => Cheque::STATUS_CLEARED]);

        if ($cheque->source_type === 'bank_transaction' && $cheque->source_id) {
            BankTransaction::where('id', $cheque->source_id)
                ->update([
                    'is_reconciled' => true,
                    'reconciled_at' => now(),
                ]);
        }

        return $cheque;
    }

    public function getRegister(int $companyId, ?int $bankAccountId = null, ?string $fromDate = null, ?string $toDate = null): \Illuminate\Support\Collection
    {
        $query = Cheque::where('company_id', $companyId)
            ->with('bankAccount')
            ->orderBy('cheque_number', 'asc');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($fromDate) {
            $query->where('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('date', '<=', $toDate);
        }

        return $query->get();
    }

    protected function getNextChequeNumber(int $companyId, int $bankAccountId): int
    {
        $bankAccount = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->first();

        $nextNumber = $bankAccount->next_cheque_number ?? 1;

        $bankAccount->update(['next_cheque_number' => $nextNumber + 1]);

        return $nextNumber;
    }

    protected function getDebitAccountForCheque(Cheque $cheque): Account
    {
        if ($cheque->journal_entry_id) {
            $line = \App\Models\JournalEntryLine::where('journal_entry_id', $cheque->journal_entry_id)
                ->where('debit', '>', 0)
                ->first();

            if ($line) {
                return Account::find($line->account_id);
            }
        }

        return Account::where('company_id', $cheque->company_id)
            ->where('code', '5000')
            ->firstOrFail();
    }
}
