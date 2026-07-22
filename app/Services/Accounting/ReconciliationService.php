<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReconciliationService
{
    public function importStatement(array $data, string $csvContent, int $userId): BankStatementImport
    {
        $requiredFields = ['bank_account_id', 'statement_date', 'statement_end_balance', 'filename'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        $companyId = $data['company_id'];
        $bankAccountId = $data['bank_account_id'];

        $account = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Bank account ID {$bankAccountId} not found or is not a bank account.");
        }

        $rows = $this->parseCsvContent($csvContent);

        return DB::transaction(function () use ($data, $userId, $companyId, $bankAccountId, $rows) {
            $import = BankStatementImport::create([
                'company_id' => $companyId,
                'bank_account_id' => $bankAccountId,
                'filename' => $data['filename'],
                'statement_date' => $data['statement_date'],
                'statement_end_balance' => $data['statement_end_balance'],
                'line_count' => count($rows),
                'imported_by' => $userId,
            ]);

            foreach ($rows as $row) {
                BankStatementLine::create([
                    'import_id' => $import->id,
                    'bank_account_id' => $bankAccountId,
                    'transaction_date' => $row['date'],
                    'description' => $row['description'],
                    'reference' => $row['reference'] ?? null,
                    'amount' => $row['amount'],
                    'is_matched' => false,
                ]);
            }

            return $import;
        });
    }

    public function parseCsvContent(string $csvContent): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $csvContent)));

        if (count($lines) < 2) {
            throw new InvalidArgumentException('CSV must contain a header row and at least one data row.');
        }

        $header = str_getcsv(array_shift($lines));
        $headerNormalized = array_map(fn($h) => strtolower(trim($h)), $header);

        $hasSeparateDebitCredit = in_array('debit', $headerNormalized) && in_array('credit', $headerNormalized);

        $dateIdx = $this->findColumnIndex($headerNormalized, ['date', 'transaction_date', 'posted_date']);
        $descIdx = $this->findColumnIndex($headerNormalized, ['description', 'desc', 'memo', 'narrative', 'details']);
        $refIdx = $this->findColumnIndex($headerNormalized, ['reference', 'ref', 'check_number', 'cheque_number', 'transaction_id'], true);
        $amountIdx = $hasSeparateDebitCredit ? null : $this->findColumnIndex($headerNormalized, ['amount', 'amt', 'value', 'total'], true);
        $debitIdx = $hasSeparateDebitCredit ? $this->findColumnIndex($headerNormalized, ['debit']) : null;
        $creditIdx = $hasSeparateDebitCredit ? $this->findColumnIndex($headerNormalized, ['credit']) : null;

        $rows = [];

        foreach ($lines as $line) {
            $values = str_getcsv($line);

            if (count($values) < count($header)) {
                $values = array_pad($values, count($header), '');
            }

            $date = $dateIdx !== null ? trim($values[$dateIdx]) : '';
            $description = $descIdx !== null ? trim($values[$descIdx]) : '';
            $reference = $refIdx !== null ? trim($values[$refIdx]) : null;

            if ($hasSeparateDebitCredit) {
                $debit = $debitIdx !== null ? $this->parseAmount($values[$debitIdx]) : 0;
                $credit = $creditIdx !== null ? $this->parseAmount($values[$creditIdx]) : 0;
                $amount = $credit - $debit;
            } else {
                $amount = $amountIdx !== null ? $this->parseAmount($values[$amountIdx]) : 0;
            }

            $rows[] = [
                'date' => $date,
                'description' => $description,
                'reference' => $reference,
                'amount' => $amount,
            ];
        }

        return $rows;
    }

    public function startReconciliation(
        int $bankAccountId,
        int $importId,
        int $companyId
    ): BankReconciliation {
        $account = Account::where('id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Bank account ID {$bankAccountId} not found or is not a bank account.");
        }

        $import = BankStatementImport::where('id', $importId)
            ->where('bank_account_id', $bankAccountId)
            ->where('company_id', $companyId)
            ->first();

        if (!$import) {
            throw new InvalidArgumentException("Bank statement import ID {$importId} not found for this bank account.");
        }

        $existingInProgress = BankReconciliation::where('bank_account_id', $bankAccountId)
            ->where('status', BankReconciliation::STATUS_IN_PROGRESS)
            ->exists();

        if ($existingInProgress) {
            throw new InvalidArgumentException('There is already an in-progress reconciliation for this bank account.');
        }

        $statementDate = $import->statement_date->format('Y-m-d');

        $bookBalance = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('company_id', $companyId)
            ->where('is_reconciled', false)
            ->where('date', '<=', $statementDate)
            ->sum('amount');

        return BankReconciliation::create([
            'company_id' => $companyId,
            'bank_account_id' => $bankAccountId,
            'import_id' => $importId,
            'statement_date' => $statementDate,
            'statement_balance' => $import->statement_end_balance,
            'book_balance' => round((float) $bookBalance, 2),
            'cleared_balance' => 0,
            'status' => BankReconciliation::STATUS_IN_PROGRESS,
        ]);
    }

    public function suggestMatches(int $reconciliationId): array
    {
        $reconciliation = BankReconciliation::findOrFail($reconciliationId);

        $import = BankReconciliation::with('import')->find($reconciliationId)->import;
        $statementDate = $import->statement_date;

        $unmatchedLines = BankStatementLine::where('import_id', $reconciliation->import_id)
            ->where('is_matched', false)
            ->get();

        $unreconciledTransactions = BankTransaction::where('bank_account_id', $reconciliation->bank_account_id)
            ->where('company_id', $reconciliation->company_id)
            ->where('is_reconciled', false)
            ->get();

        $matchedTransactionIds = BankReconciliationItem::where('reconciliation_id', $reconciliationId)
            ->whereNotNull('bank_transaction_id')
            ->pluck('bank_transaction_id')
            ->toArray();

        $suggestions = [];

        foreach ($unmatchedLines as $line) {
            foreach ($unreconciledTransactions as $transaction) {
                if (in_array($transaction->id, $matchedTransactionIds)) {
                    continue;
                }

                $amountMatch = round((float) $line->amount, 2) === round((float) $transaction->amount, 2);

                if (!$amountMatch) {
                    continue;
                }

                $lineDate = $line->transaction_date;
                $txDate = $transaction->date;
                $daysDiff = abs($lineDate->diffInDays($txDate));

                if ($daysDiff <= 5) {
                    $confidence = $daysDiff === 0 ? 'high' : 'medium';

                    $alreadySuggested = collect($suggestions)->contains(
                        fn($s) => $s['bank_statement_line_id'] === $line->id
                    );

                    if (!$alreadySuggested) {
                        $suggestions[] = [
                            'bank_statement_line_id' => $line->id,
                            'bank_transaction_id' => $transaction->id,
                            'confidence' => $confidence,
                        ];
                    }
                }
            }
        }

        return $suggestions;
    }

    public function matchItems(int $reconciliationId, array $matches): void
    {
        $reconciliation = BankReconciliation::findOrFail($reconciliationId);

        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            throw new InvalidArgumentException('Can only match items on an in-progress reconciliation.');
        }

        DB::transaction(function () use ($reconciliation, $matches) {
            foreach ($matches as $match) {
                $statementLineId = $match['bank_statement_line_id'] ?? null;
                $transactionId = $match['bank_transaction_id'] ?? null;
                $amount = $match['amount'];

                if ($statementLineId) {
                    $line = BankStatementLine::where('id', $statementLineId)
                        ->where('import_id', $reconciliation->import_id)
                        ->first();

                    if (!$line) {
                        throw new InvalidArgumentException("Statement line ID {$statementLineId} not found for this import.");
                    }
                }

                if ($transactionId) {
                    $transaction = BankTransaction::where('id', $transactionId)
                        ->where('bank_account_id', $reconciliation->bank_account_id)
                        ->where('is_reconciled', false)
                        ->first();

                    if (!$transaction) {
                        throw new InvalidArgumentException("Bank transaction ID {$transactionId} not found or already reconciled.");
                    }
                }

                BankReconciliationItem::create([
                    'reconciliation_id' => $reconciliation->id,
                    'bank_statement_line_id' => $statementLineId,
                    'bank_transaction_id' => $transactionId,
                    'amount' => $amount,
                ]);

                if ($statementLineId) {
                    BankStatementLine::where('id', $statementLineId)
                        ->update(['is_matched' => true]);
                }
            }
        });
    }

    public function unmatchItem(int $itemId): void
    {
        $item = BankReconciliationItem::findOrFail($itemId);
        $reconciliation = BankReconciliation::findOrFail($item->reconciliation_id);

        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            throw new InvalidArgumentException('Can only unmatch items on an in-progress reconciliation.');
        }

        DB::transaction(function () use ($item) {
            if ($item->bank_statement_line_id) {
                $hasOtherMatches = BankReconciliationItem::where('bank_statement_line_id', $item->bank_statement_line_id)
                    ->where('id', '!=', $item->id)
                    ->exists();

                if (!$hasOtherMatches) {
                    BankStatementLine::where('id', $item->bank_statement_line_id)
                        ->update(['is_matched' => false]);
                }
            }

            $item->delete();
        });
    }

    public function completeReconciliation(int $reconciliationId, int $userId): BankReconciliation
    {
        $reconciliation = BankReconciliation::with('import')->findOrFail($reconciliationId);

        if ($reconciliation->status !== BankReconciliation::STATUS_IN_PROGRESS) {
            throw new InvalidArgumentException('Reconciliation is not in progress.');
        }

        $statementBalance = (float) $reconciliation->statement_balance;

        $clearedBalance = BankReconciliationItem::where('reconciliation_id', $reconciliationId)
            ->whereNotNull('bank_transaction_id')
            ->join('bank_transactions', 'bank_reconciliation_items.bank_transaction_id', '=', 'bank_transactions.id')
            ->sum('bank_transactions.amount');

        $clearedBalance = round((float) $clearedBalance, 2);
        $difference = round($statementBalance - $clearedBalance, 2);

        if (abs($difference) > 0.01) {
            throw new InvalidArgumentException(
                "Statement balance (" . number_format($statementBalance, 2) .
                ") does not match cleared balance (" . number_format($clearedBalance, 2) .
                "). Difference: " . number_format($difference, 2) .
                ". Please match all items before completing."
            );
        }

        return DB::transaction(function () use ($reconciliation, $reconciliationId, $userId, $clearedBalance) {
            $matchedTransactionIds = BankReconciliationItem::where('reconciliation_id', $reconciliationId)
                ->whereNotNull('bank_transaction_id')
                ->pluck('bank_transaction_id')
                ->toArray();

            BankTransaction::whereIn('id', $matchedTransactionIds)
                ->update([
                    'is_reconciled' => true,
                    'reconciled_at' => now(),
                    'bank_reconciliation_id' => $reconciliationId,
                ]);

            $reconciliation->update([
                'cleared_balance' => $clearedBalance,
                'status' => BankReconciliation::STATUS_COMPLETED,
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            return $reconciliation;
        });
    }

    public function getReconciliationSummary(int $reconciliationId): array
    {
        $reconciliation = BankReconciliation::findOrFail($reconciliationId);

        $clearedBalance = BankReconciliationItem::where('reconciliation_id', $reconciliationId)
            ->whereNotNull('bank_transaction_id')
            ->join('bank_transactions', 'bank_reconciliation_items.bank_transaction_id', '=', 'bank_transactions.id')
            ->sum('bank_transactions.amount');

        $clearedBalance = round((float) $clearedBalance, 2);
        $statementBalance = (float) $reconciliation->statement_balance;

        return [
            'statement_balance' => $statementBalance,
            'book_balance' => (float) $reconciliation->book_balance,
            'cleared_balance' => $clearedBalance,
            'difference' => round($statementBalance - $clearedBalance, 2),
        ];
    }

    protected function findColumnIndex(array $headers, array $candidates, bool $optional = false): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $headers);
            if ($index !== false) {
                return $index;
            }
        }

        if ($optional) {
            return null;
        }

        throw new InvalidArgumentException(
            "Required column not found. Expected one of: " . implode(', ', $candidates) .
            ". Found columns: " . implode(', ', $headers)
        );
    }

    protected function parseAmount(string $value): float
    {
        $cleaned = preg_replace('/[^\d.\-]/', '', trim($value));

        if ($cleaned === '' || $cleaned === null) {
            return 0;
        }

        return (float) $cleaned;
    }
}
