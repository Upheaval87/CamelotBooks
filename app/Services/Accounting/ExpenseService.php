<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\DefaultAccountMapping;
use App\Models\Expense;
use App\Models\ExpenseLine;
use App\Models\Vendor;
use App\Services\Accounting\ForeignCurrencyService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    protected JournalPostingEngine $postingEngine;
    protected ForeignCurrencyService $fxService;

    public function __construct(JournalPostingEngine $postingEngine, ForeignCurrencyService $fxService)
    {
        $this->postingEngine = $postingEngine;
        $this->fxService = $fxService;
    }

    public function create(array $data, int $userId): Expense
    {
        $companyId = $data['company_id'];

        if (isset($data['vendor_id']) && $data['vendor_id']) {
            $this->validateVendor($companyId, $data['vendor_id']);
        }

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one expense line is required.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $expenseNumber = $this->generateExpenseNumber($companyId);

            $expense = Expense::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'expense_number' => $expenseNumber,
                'reference' => $data['reference'] ?? null,
                'expense_date' => $data['expense_date'],
                'memo' => $data['memo'] ?? null,
                'status' => Expense::STATUS_DRAFT,
                'amount' => 0,
                'currency' => $data['currency'] ?? 'USD',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->createLine($expense, $lineData, $companyId);
            }

            $this->updateExpenseAmount($expense);

            $this->logExpenseAction($expense, 'created', null, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function update(Expense $expense, array $data, int $userId): Expense
    {
        if ($expense->status !== Expense::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft expenses can be updated.');
        }

        $companyId = $expense->company_id;

        if (isset($data['vendor_id']) && $data['vendor_id']) {
            $this->validateVendor($companyId, $data['vendor_id']);
        }

        return DB::transaction(function () use ($expense, $data, $userId, $companyId) {
            $oldValues = $expense->toArray();

            $expense->update([
                'vendor_id' => $data['vendor_id'] ?? $expense->vendor_id,
                'expense_date' => $data['expense_date'] ?? $expense->expense_date,
                'bank_account_id' => $data['bank_account_id'] ?? $expense->bank_account_id,
                'reference' => $data['reference'] ?? $expense->reference,
                'memo' => $data['memo'] ?? $expense->memo,
                'branch_id' => $data['branch_id'] ?? $expense->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $expense->cost_center_id,
                'currency' => $data['currency'] ?? $expense->currency,
                'exchange_rate' => $data['exchange_rate'] ?? $expense->exchange_rate,
            ]);

            if (isset($data['lines'])) {
                $expense->lines()->delete();

                foreach ($data['lines'] as $lineData) {
                    $this->createLine($expense, $lineData, $companyId);
                }
            }

            $this->updateExpenseAmount($expense);

            $this->logExpenseAction($expense, 'updated', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function post(Expense $expense, int $userId): Expense
    {
        if ($expense->status !== Expense::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft expenses can be posted.');
        }

        $companyId = $expense->company_id;
        $bankAccount = $expense->bank_account_id
            ? $this->findAccountById($companyId, $expense->bank_account_id)
            : DefaultAccountMapping::getAccount($companyId, 'default_bank');

        return DB::transaction(function () use ($expense, $userId, $companyId, $bankAccount) {
            $oldValues = $expense->toArray();

            $lines = $expense->lines()->get();
            $taxReceivableAccount = DefaultAccountMapping::getAccount($companyId, 'tax_receivable');
            $jeLines = [];
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $jeLines[] = [
                    'account_id' => $line->expense_account_id,
                    'debit' => $line->amount,
                    'credit' => 0,
                    'memo' => "Expense {$expense->expense_number} - {$line->description}",
                    'entity_type' => Expense::class,
                    'entity_id' => $expense->id,
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalDebit += $line->amount;

                if ($line->tax_amount > 0) {
                    $jeLines[] = [
                        'account_id' => $taxReceivableAccount->id,
                        'debit' => $line->tax_amount,
                        'credit' => 0,
                        'memo' => "Expense {$expense->expense_number} - Tax - {$line->description}",
                        'entity_type' => Expense::class,
                        'entity_id' => $expense->id,
                        'cost_center_id' => $line->cost_center_id,
                    ];
                    $totalDebit += $line->tax_amount;
                }

                $jeLines[] = [
                    'account_id' => $bankAccount->id,
                    'debit' => 0,
                    'credit' => $line->line_total,
                    'memo' => "Expense {$expense->expense_number} - {$line->description}",
                    'entity_type' => Expense::class,
                    'entity_id' => $expense->id,
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalCredit += $line->line_total;
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $diff = round($totalDebit - $totalCredit, 2);
                $roundingAccountId = DefaultAccountMapping::getAccountId($companyId, 'rounding');

                if ($roundingAccountId && abs($diff) <= 0.05 && abs($diff) > 0) {
                    if ($diff > 0) {
                        $jeLines[] = [
                            'account_id' => $roundingAccountId,
                            'debit' => 0,
                            'credit' => abs($diff),
                            'memo' => 'Rounding adjustment',
                        ];
                        $totalCredit += abs($diff);
                    } else {
                        $jeLines[] = [
                            'account_id' => $roundingAccountId,
                            'debit' => abs($diff),
                            'credit' => 0,
                            'memo' => 'Rounding adjustment',
                        ];
                        $totalDebit += abs($diff);
                    }
                } else {
                    throw new InvalidArgumentException(
                        "Journal entry does not balance. Debit: " . number_format($totalDebit, 2) .
                        ", Credit: " . number_format($totalCredit, 2)
                    );
                }
            }

            $this->fxService->postExpenseInForeignCurrency($expense, $jeLines, $userId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $expense->expense_date->format('Y-m-d'),
                'source_module' => 'expense',
                'reference' => $expense->expense_number,
                'memo' => "Expense {$expense->expense_number}",
                'branch_id' => $expense->branch_id,
                'lines' => $jeLines,
            ]);

            $expense->update([
                'status' => Expense::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
                'base_amount' => round((float) $expense->amount * (float) $expense->exchange_rate, 2),
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logExpenseAction($expense, 'posted', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function void(Expense $expense, string $reason, int $userId): Expense
    {
        if ($expense->status === Expense::STATUS_VOID) {
            throw new InvalidArgumentException('This expense is already voided.');
        }

        if ($expense->status === Expense::STATUS_DRAFT) {
            throw new InvalidArgumentException('Draft expenses cannot be voided. Delete them instead.');
        }

        if (!$expense->journal_entry_id) {
            throw new InvalidArgumentException('This expense has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($expense, $reason, $userId) {
            $oldValues = $expense->toArray();

            $this->postingEngine->reverse($expense->journal_entry_id, $userId);

            $expense->update([
                'status' => Expense::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logExpenseAction($expense, 'voided', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function updateExpenseAmount(Expense $expense): void
    {
        $total = (float) $expense->lines()->sum('line_total');

        $expense->update(['amount' => round($total, 2)]);
    }

    public function computeLineTotals(array $lineData): array
    {
        $quantity = (float) ($lineData['quantity'] ?? 1);
        $unitPrice = (float) ($lineData['unit_price'] ?? 0);
        $discount = (float) ($lineData['discount'] ?? 0);
        $taxRate = (float) ($lineData['tax_rate'] ?? 0);

        $amount = ($quantity * $unitPrice) - $discount;
        $taxAmount = $amount * $taxRate / 100;
        $lineTotal = $amount + $taxAmount;

        return [
            'amount' => round($amount, 2),
            'tax_amount' => round($taxAmount, 2),
            'line_total' => round($lineTotal, 2),
        ];
    }

    protected function createLine(Expense $expense, array $lineData, int $companyId): ExpenseLine
    {
        if (isset($lineData['product_id'])) {
            $this->validateProduct($companyId, $lineData['product_id']);
        }

        $this->validateAccount($companyId, $lineData['expense_account_id']);

        $quantity = (float) ($lineData['quantity'] ?? 1);
        $totals = $this->computeLineTotals(array_merge($lineData, ['quantity' => $quantity]));

        return ExpenseLine::create([
            'expense_id' => $expense->id,
            'product_id' => $lineData['product_id'] ?? null,
            'description' => $lineData['description'],
            'quantity' => $quantity,
            'unit_price' => $lineData['unit_price'],
            'discount' => $lineData['discount'] ?? 0,
            'tax_rate' => $lineData['tax_rate'] ?? 0,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'line_total' => $totals['line_total'],
            'expense_account_id' => $lineData['expense_account_id'],
            'cost_center_id' => $lineData['cost_center_id'] ?? null,
        ]);
    }

    protected function validateVendor(int $companyId, int $vendorId): void
    {
        $vendor = Vendor::where('id', $vendorId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$vendor) {
            throw new InvalidArgumentException("Vendor ID {$vendorId} not found or inactive for this company.");
        }
    }

    protected function validateProduct(int $companyId, int $productId): void
    {
        $product = \App\Models\Product::where('id', $productId)
            ->where('company_id', $companyId)
            ->first();

        if (!$product) {
            throw new InvalidArgumentException("Product ID {$productId} not found for this company.");
        }
    }

    protected function validateAccount(int $companyId, int $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account ID {$accountId} not found for this company.");
        }
    }

    protected function findAccountByCode(int $companyId, string $code): Account
    {
        $account = Account::where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account with code {$code} not found for this company.");
        }

        return $account;
    }

    protected function findAccountById(int $companyId, int $accountId): Account
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account ID {$accountId} not found for this company.");
        }

        return $account;
    }

    protected function generateExpenseNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'EXP-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastExpense = Expense::where('company_id', $companyId)
            ->where('expense_number', 'like', $prefix . '%')
            ->orderByDesc('expense_number')
            ->first();

        if ($lastExpense) {
            $lastSequence = (int) substr($lastExpense->expense_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logExpenseAction(Expense $expense, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $expense->company_id,
            'journalable_type' => Expense::class,
            'journalable_id' => $expense->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
