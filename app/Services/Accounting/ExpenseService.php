<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\DefaultAccountMapping;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseClaim;
use App\Models\ExpenseLine;
use App\Models\ExpensePayment;
use App\Models\Vendor;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    protected JournalPostingEngine $postingEngine;
    protected ForeignCurrencyService $fxService;
    protected BudgetCheckService $budgetCheckService;
    protected NumberingSequenceService $numberingService;

    public function __construct(
        JournalPostingEngine $postingEngine,
        ForeignCurrencyService $fxService,
        BudgetCheckService $budgetCheckService,
        NumberingSequenceService $numberingService,
    ) {
        $this->postingEngine = $postingEngine;
        $this->fxService = $fxService;
        $this->budgetCheckService = $budgetCheckService;
        $this->numberingService = $numberingService;
    }

    public function create(array $data, int $userId): Expense
    {
        $companyId = $data['company_id'];

        if (isset($data['vendor_id']) && $data['vendor_id']) {
            $this->validateVendor($companyId, $data['vendor_id']);
        }

        if (isset($data['category_id']) && $data['category_id']) {
            $this->validateCategory($companyId, $data['category_id']);
        }

        if (isset($data['employee_id']) && $data['employee_id']) {
            $this->validateEmployee($companyId, $data['employee_id']);
        }

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one expense line is required.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $expenseNumber = $this->generateExpenseNumber($companyId);

            $currency = $data['currency'] ?? 'USD';
            $exchangeRate = (float) ($data['exchange_rate'] ?? 1);

            $expense = Expense::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'department' => $data['department'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'expense_number' => $expenseNumber,
                'reference' => $data['reference'] ?? null,
                'expense_date' => $data['expense_date'],
                'memo' => $data['memo'] ?? null,
                'status' => Expense::STATUS_DRAFT,
                'amount' => 0,
                'subtotal' => 0,
                'tax_total' => 0,
                'discount' => 0,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'base_amount' => round(0 * $exchangeRate, 2),
                'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_account_id' => $data['payment_account_id'] ?? $data['bank_account_id'] ?? null,
                'payment_date' => $data['payment_date'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
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
        if (!$expense->isEditable()) {
            throw new InvalidArgumentException('Only draft or returned expenses can be updated.');
        }

        $companyId = $expense->company_id;

        if (isset($data['vendor_id']) && $data['vendor_id']) {
            $this->validateVendor($companyId, $data['vendor_id']);
        }

        if (isset($data['category_id']) && $data['category_id']) {
            $this->validateCategory($companyId, $data['category_id']);
        }

        if (isset($data['employee_id']) && $data['employee_id']) {
            $this->validateEmployee($companyId, $data['employee_id']);
        }

        return DB::transaction(function () use ($expense, $data, $userId, $companyId) {
            $oldValues = $expense->toArray();

            $expense->update([
                'vendor_id' => $data['vendor_id'] ?? $expense->vendor_id,
                'branch_id' => $data['branch_id'] ?? $expense->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $expense->cost_center_id,
                'category_id' => $data['category_id'] ?? $expense->category_id,
                'department' => $data['department'] ?? $expense->department,
                'employee_id' => $data['employee_id'] ?? $expense->employee_id,
                'expense_date' => $data['expense_date'] ?? $expense->expense_date,
                'bank_account_id' => $data['bank_account_id'] ?? $expense->bank_account_id,
                'reference' => $data['reference'] ?? $expense->reference,
                'memo' => $data['memo'] ?? $expense->memo,
                'currency' => $data['currency'] ?? $expense->currency,
                'exchange_rate' => $data['exchange_rate'] ?? $expense->exchange_rate,
                'payment_method' => $data['payment_method'] ?? $expense->payment_method,
                'payment_account_id' => $data['payment_account_id'] ?? $expense->payment_account_id,
                'payment_date' => $data['payment_date'] ?? $expense->payment_date,
                'payment_reference' => $data['payment_reference'] ?? $expense->payment_reference,
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

    public function submit(Expense $expense, int $userId): Expense
    {
        if (!$expense->isEditable()) {
            throw new InvalidArgumentException('Only draft or returned expenses can be submitted for approval.');
        }

        return DB::transaction(function () use ($expense, $userId) {
            $oldValues = $expense->toArray();

            $this->recordBudgetCheck($expense);

            $expense->update([
                'status' => Expense::STATUS_PENDING,
                'submitted_by' => $userId,
                'submitted_at' => now(),
            ]);

            $this->logExpenseAction($expense, 'submitted_for_approval', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function approve(Expense $expense, int $userId): Expense
    {
        if (!$expense->isPending()) {
            throw new InvalidArgumentException('Only pending expenses can be approved.');
        }

        return DB::transaction(function () use ($expense, $userId) {
            $oldValues = $expense->toArray();

            $expense->update([
                'status' => Expense::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            $this->logExpenseAction($expense, 'approved', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function reject(Expense $expense, string $reason, int $userId): Expense
    {
        if (!$expense->isPending()) {
            throw new InvalidArgumentException('Only pending expenses can be rejected.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($expense, $reason, $userId) {
            $oldValues = $expense->toArray();

            $expense->update([
                'status' => Expense::STATUS_REJECTED,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->logExpenseAction($expense, 'rejected', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function returnForCorrection(Expense $expense, string $reason, int $userId): Expense
    {
        if (!$expense->isPending()) {
            throw new InvalidArgumentException('Only pending expenses can be returned for correction.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A return reason is required.');
        }

        return DB::transaction(function () use ($expense, $reason, $userId) {
            $oldValues = $expense->toArray();

            $expense->update([
                'status' => Expense::STATUS_RETURNED,
                'returned_by' => $userId,
                'returned_at' => now(),
                'return_reason' => $reason,
            ]);

            $this->logExpenseAction($expense, 'returned_for_correction', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    /**
     * Record the budget override details (reason + approver) required before
     * an over-budget expense may be posted.
     */
    public function authorizeBudget(Expense $expense, string $reason, int $approverId, int $userId): Expense
    {
        if ($expense->isVoid()) {
            throw new InvalidArgumentException('Void expenses cannot be budget-authorized.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A budget override reason is required.');
        }

        return DB::transaction(function () use ($expense, $reason, $approverId, $userId) {
            $oldValues = $expense->toArray();

            $expense->update([
                'budget_reason' => $reason,
                'budget_approver_id' => $approverId,
                'budget_approved_at' => now(),
            ]);

            $this->logExpenseAction($expense, 'budget_authorized', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function post(Expense $expense, int $userId): Expense
    {
        if (!$expense->isApproved() && !$expense->isDraft()) {
            throw new InvalidArgumentException('Only approved or draft expenses can be posted.');
        }

        $companyId = $expense->company_id;
        $apAccount = DefaultAccountMapping::getAccount($companyId, 'accounts_payable');

        if (!$apAccount) {
            throw new InvalidArgumentException('No accounts payable account mapped for this company.');
        }

        return DB::transaction(function () use ($expense, $userId, $companyId, $apAccount) {
            $oldValues = $expense->toArray();

            $this->recordBudgetCheck($expense);

            if ($expense->budget_check === 'exceeded') {
                if (!$expense->budget_reason || !$expense->budget_approver_id || !$expense->budget_approved_at) {
                    throw new InvalidArgumentException(
                        'This expense exceeds the approved budget. Provide an override reason and budget approver before posting.'
                    );
                }
            }

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
                $totalDebit += (float) $line->amount;

                if ((float) $line->tax_amount > 0) {
                    $jeLines[] = [
                        'account_id' => $taxReceivableAccount?->id ?? $line->expense_account_id,
                        'debit' => $line->tax_amount,
                        'credit' => 0,
                        'memo' => "Expense {$expense->expense_number} - Tax - {$line->description}",
                        'entity_type' => Expense::class,
                        'entity_id' => $expense->id,
                        'cost_center_id' => $line->cost_center_id,
                    ];
                    $totalDebit += (float) $line->tax_amount;
                }

                $jeLines[] = [
                    'account_id' => $apAccount->id,
                    'debit' => 0,
                    'credit' => $line->line_total,
                    'memo' => "Expense {$expense->expense_number} - {$line->description}",
                    'entity_type' => Expense::class,
                    'entity_id' => $expense->id,
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalCredit += (float) $line->line_total;
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

    public function recordPayment(Expense $expense, array $data, int $userId): ExpensePayment
    {
        if (!$expense->isPosted()) {
            throw new InvalidArgumentException('Only posted expenses can have payments recorded.');
        }

        if ($expense->isPaidOut()) {
            throw new InvalidArgumentException('This expense is already paid.');
        }

        $companyId = $expense->company_id;
        $apAccount = DefaultAccountMapping::getAccount($companyId, 'accounts_payable');

        if (!$apAccount) {
            throw new InvalidArgumentException('No accounts payable account mapped for this company.');
        }

        $paymentAccountId = (int) ($data['payment_account_id'] ?? $data['bank_account_id'] ?? $expense->payment_account_id ?? $expense->bank_account_id ?? 0);
        $paymentAccount = $this->findAccountById($companyId, $paymentAccountId);

        $amount = round((float) ($data['amount'] ?? $expense->amount), 2);

        return DB::transaction(function () use ($expense, $data, $userId, $companyId, $apAccount, $paymentAccount, $amount) {
            $payment = ExpensePayment::create([
                'company_id' => $companyId,
                'expense_id' => $expense->id,
                'payment_number' => 'PAY-' . $expense->expense_number,
                'amount' => $amount,
                'payment_date' => $data['payment_date'] ?? $expense->payment_date ?? now()->format('Y-m-d'),
                'payment_method' => $data['payment_method'] ?? $expense->payment_method ?? 'bank_transfer',
                'account_id' => $paymentAccount->id,
                'reference' => $data['reference'] ?? $expense->payment_reference ?? null,
                'status' => ExpensePayment::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
                'paid_by' => $userId,
                'paid_at' => now(),
                'created_by' => $userId,
            ]);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => ($data['payment_date'] ?? $expense->payment_date ?? now()) instanceof \DateTimeInterface
                    ? ($data['payment_date'] ?? $expense->payment_date ?? now())->format('Y-m-d')
                    : ($data['payment_date'] ?? $expense->expense_date->format('Y-m-d')),
                'source_module' => 'expense_payment',
                'reference' => $payment->payment_number,
                'memo' => "Payment of expense {$expense->expense_number}",
                'branch_id' => $expense->branch_id,
                'lines' => [
                    [
                        'account_id' => $apAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "Payment of expense {$expense->expense_number}",
                        'entity_type' => Expense::class,
                        'entity_id' => $expense->id,
                    ],
                    [
                        'account_id' => $paymentAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Payment of expense {$expense->expense_number}",
                        'entity_type' => Expense::class,
                        'entity_id' => $expense->id,
                    ],
                ],
            ]);

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            $totalPaid = round((float) $expense->payments()->where('status', ExpensePayment::STATUS_COMPLETED)->sum('amount'), 2);
            $fullyPaid = $totalPaid >= round((float) $expense->amount, 2);

            $expense->update([
                'payment_status' => $fullyPaid ? Expense::PAYMENT_STATUS_PAID : Expense::PAYMENT_STATUS_UNPAID,
                'payment_method' => $data['payment_method'] ?? $expense->payment_method,
                'payment_account_id' => $paymentAccount->id,
                'payment_date' => $data['payment_date'] ?? $expense->payment_date,
                'payment_reference' => $data['reference'] ?? $expense->payment_reference,
                'status' => $fullyPaid ? Expense::STATUS_PAID : Expense::STATUS_POSTED,
            ]);

            $this->logExpenseAction($expense, 'payment_recorded', ['payment_status' => Expense::PAYMENT_STATUS_UNPAID], ['payment_status' => $expense->payment_status], $userId);

            return $payment;
        });
    }

    public function void(Expense $expense, string $reason, int $userId): Expense
    {
        if ($expense->isVoid()) {
            throw new InvalidArgumentException('This expense is already voided.');
        }

        if ($expense->isDraft() || $expense->isReturned()) {
            throw new InvalidArgumentException('Draft expenses cannot be voided. Delete them instead.');
        }

        if (!$expense->journal_entry_id) {
            throw new InvalidArgumentException('This expense has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($expense, $reason, $userId) {
            $oldValues = $expense->toArray();

            foreach ($expense->payments()->where('status', ExpensePayment::STATUS_COMPLETED)->get() as $payment) {
                if ($payment->journal_entry_id) {
                    $this->postingEngine->reverse($payment->journal_entry_id, $userId);
                }

                $payment->update([
                    'status' => ExpensePayment::STATUS_REVERSED,
                ]);
            }

            $this->postingEngine->reverse($expense->journal_entry_id, $userId);

            $expense->update([
                'status' => Expense::STATUS_VOID,
                'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logExpenseAction($expense, 'voided', $oldValues, $expense->toArray(), $userId);

            return $expense;
        });
    }

    public function duplicate(Expense $expense, int $userId): Expense
    {
        $companyId = $expense->company_id;

        return DB::transaction(function () use ($expense, $userId, $companyId) {
            $expenseNumber = $this->generateExpenseNumber($companyId);

            $copy = Expense::create([
                'company_id' => $companyId,
                'branch_id' => $expense->branch_id,
                'cost_center_id' => $expense->cost_center_id,
                'vendor_id' => $expense->vendor_id,
                'bank_account_id' => $expense->bank_account_id,
                'category_id' => $expense->category_id,
                'department' => $expense->department,
                'employee_id' => $expense->employee_id,
                'expense_number' => $expenseNumber,
                'reference' => $expense->reference,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'memo' => $expense->memo,
                'status' => Expense::STATUS_DRAFT,
                'amount' => 0,
                'subtotal' => 0,
                'tax_total' => 0,
                'discount' => 0,
                'currency' => $expense->currency,
                'exchange_rate' => $expense->exchange_rate,
                'base_amount' => 0,
                'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
                'payment_method' => $expense->payment_method,
                'payment_account_id' => $expense->payment_account_id,
                'created_by' => $userId,
            ]);

            foreach ($expense->lines as $line) {
                ExpenseLine::create([
                    'expense_id' => $copy->id,
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount' => $line->discount,
                    'tax_rate' => $line->tax_rate,
                    'amount' => $line->amount,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'expense_account_id' => $line->expense_account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'department' => $line->department,
                ]);
            }

            $this->updateExpenseAmount($copy);

            $this->logExpenseAction($copy, 'duplicated_from', null, [
                'source_expense_id' => $expense->id,
                'source_expense_number' => $expense->expense_number,
            ], $userId);

            return $copy;
        });
    }

    public function destroy(Expense $expense, int $userId): void
    {
        if (!$expense->isEditable()) {
            throw new InvalidArgumentException('Only draft or returned expenses can be deleted.');
        }

        DB::transaction(function () use ($expense, $userId) {
            $this->logExpenseAction($expense, 'deleted', $expense->toArray(), null, $userId);

            $expense->lines()->delete();
            $expense->attachments()->delete();
            $expense->delete();
        });
    }

    public function updateExpenseAmount(Expense $expense): void
    {
        $subtotal = (float) $expense->lines()->sum('amount');
        $taxTotal = (float) $expense->lines()->sum('tax_amount');
        $discount = (float) $expense->lines()->sum('discount');
        $total = $subtotal + $taxTotal;

        $expense->update([
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'discount' => round($discount, 2),
            'amount' => round($total, 2),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Expense Claims
    // ─────────────────────────────────────────────────────────────

    public function createClaim(array $data, int $userId): ExpenseClaim
    {
        $companyId = $data['company_id'];

        if (isset($data['employee_id']) && $data['employee_id']) {
            $this->validateEmployee($companyId, $data['employee_id']);
        }

        if (isset($data['category_id']) && $data['category_id']) {
            $this->validateCategory($companyId, $data['category_id']);
        }

        if (!isset($data['amount']) || (float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('A claim amount greater than zero is required.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $claimNumber = $this->generateClaimNumber($companyId);
            $currency = $data['currency'] ?? 'USD';
            $exchangeRate = (float) ($data['exchange_rate'] ?? 1);

            return ExpenseClaim::create([
                'company_id' => $companyId,
                'claim_number' => $claimNumber,
                'employee_id' => $data['employee_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'expense_date' => $data['expense_date'],
                'amount' => (float) $data['amount'],
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'base_amount' => round((float) $data['amount'] * $exchangeRate, 2),
                'payment_method' => $data['payment_method'] ?? null,
                'reimburse_to' => $data['reimburse_to'] ?? null,
                'description' => $data['description'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => ExpenseClaim::STATUS_DRAFT,
                'created_by' => $userId,
            ]);
        });
    }

    public function updateClaim(ExpenseClaim $claim, array $data, int $userId): ExpenseClaim
    {
        if (!$claim->isDraft()) {
            throw new InvalidArgumentException('Only draft claims can be updated.');
        }

        $companyId = $claim->company_id;

        if (isset($data['employee_id']) && $data['employee_id']) {
            $this->validateEmployee($companyId, $data['employee_id']);
        }

        if (isset($data['category_id']) && $data['category_id']) {
            $this->validateCategory($companyId, $data['category_id']);
        }

        return DB::transaction(function () use ($claim, $data, $userId, $companyId) {
            $oldValues = $claim->toArray();

            $claim->update([
                'employee_id' => $data['employee_id'] ?? $claim->employee_id,
                'branch_id' => $data['branch_id'] ?? $claim->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $claim->cost_center_id,
                'category_id' => $data['category_id'] ?? $claim->category_id,
                'expense_date' => $data['expense_date'] ?? $claim->expense_date,
                'amount' => (float) ($data['amount'] ?? $claim->amount),
                'currency' => $data['currency'] ?? $claim->currency,
                'exchange_rate' => $data['exchange_rate'] ?? $claim->exchange_rate,
                'payment_method' => $data['payment_method'] ?? $claim->payment_method,
                'reimburse_to' => $data['reimburse_to'] ?? $claim->reimburse_to,
                'description' => $data['description'] ?? $claim->description,
                'memo' => $data['memo'] ?? $claim->memo,
            ]);

            $claim->update([
                'base_amount' => round((float) $claim->amount * (float) $claim->exchange_rate, 2),
            ]);

            $this->logExpenseAction($claim, 'updated', $oldValues, $claim->toArray(), $userId);

            return $claim;
        });
    }

    public function submitClaim(ExpenseClaim $claim, int $userId): ExpenseClaim
    {
        if (!$claim->isDraft()) {
            throw new InvalidArgumentException('Only draft claims can be submitted for approval.');
        }

        return DB::transaction(function () use ($claim, $userId) {
            $oldValues = $claim->toArray();

            $claim->update([
                'status' => ExpenseClaim::STATUS_PENDING,
                'submitted_by' => $userId,
                'submitted_at' => now(),
            ]);

            $this->logExpenseAction($claim, 'submitted_for_approval', $oldValues, $claim->toArray(), $userId);

            return $claim;
        });
    }

    public function approveClaim(ExpenseClaim $claim, int $userId): ExpenseClaim
    {
        if (!$claim->isPending()) {
            throw new InvalidArgumentException('Only pending claims can be approved.');
        }

        return DB::transaction(function () use ($claim, $userId) {
            $oldValues = $claim->toArray();

            $expense = $this->createExpenseFromClaim($claim, $userId);

            $claim->update([
                'status' => ExpenseClaim::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'expense_id' => $expense->id,
            ]);

            $this->logExpenseAction($claim, 'approved', $oldValues, $claim->toArray(), $userId);

            return $claim;
        });
    }

    public function rejectClaim(ExpenseClaim $claim, string $reason, int $userId): ExpenseClaim
    {
        if (!$claim->isPending()) {
            throw new InvalidArgumentException('Only pending claims can be rejected.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($claim, $reason, $userId) {
            $oldValues = $claim->toArray();

            $claim->update([
                'status' => ExpenseClaim::STATUS_REJECTED,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->logExpenseAction($claim, 'rejected', $oldValues, $claim->toArray(), $userId);

            return $claim;
        });
    }

    public function reimburseClaim(ExpenseClaim $claim, int $userId): ExpenseClaim
    {
        if (!$claim->isApproved()) {
            throw new InvalidArgumentException('Only approved claims can be reimbursed.');
        }

        if (!$claim->expense_id) {
            throw new InvalidArgumentException('This claim has no linked expense to reimburse.');
        }

        return DB::transaction(function () use ($claim, $userId) {
            $oldValues = $claim->toArray();

            $expense = $claim->expense;

            if ($expense && $expense->isPosted() && !$expense->isPaidOut()) {
                $this->recordPayment($expense, [
                    'amount' => $expense->amount,
                    'payment_method' => $claim->payment_method ?? 'bank_transfer',
                    'payment_account_id' => $expense->payment_account_id ?? $expense->bank_account_id,
                    'payment_date' => now()->format('Y-m-d'),
                    'reference' => 'Reimbursement of ' . $claim->claim_number,
                ], $userId);
            }

            $claim->update([
                'status' => ExpenseClaim::STATUS_REIMBURSED,
                'reimbursed_by' => $userId,
                'reimbursed_at' => now(),
            ]);

            $this->logExpenseAction($claim, 'reimbursed', $oldValues, $claim->toArray(), $userId);

            return $claim;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    protected function createExpenseFromClaim(ExpenseClaim $claim, int $userId): Expense
    {
        $companyId = $claim->company_id;

        $expenseAccountId = DefaultAccountMapping::getAccountId($companyId, 'default_expense')
            ?? Account::where('company_id', $companyId)
                ->where('type', 'expense')
                ->where('is_active', true)
                ->orderBy('code')
                ->value('id');

        if (!$expenseAccountId) {
            throw new InvalidArgumentException('No default expense account mapped for this company. Map one in Settings before approving claims.');
        }

        $expenseNumber = $this->generateExpenseNumber($companyId);

        $expense = Expense::create([
            'company_id' => $companyId,
            'branch_id' => $claim->branch_id,
            'cost_center_id' => $claim->cost_center_id,
            'category_id' => $claim->category_id,
            'employee_id' => $claim->employee_id,
            'expense_number' => $expenseNumber,
            'expense_date' => $claim->expense_date->format('Y-m-d'),
            'memo' => $claim->memo ?? $claim->description ?? ('Employee claim ' . $claim->claim_number),
            'status' => Expense::STATUS_DRAFT,
            'amount' => 0,
            'subtotal' => 0,
            'tax_total' => 0,
            'discount' => 0,
            'currency' => $claim->currency,
            'exchange_rate' => $claim->exchange_rate,
            'base_amount' => $claim->base_amount,
            'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
            'claim_id' => $claim->id,
            'created_by' => $userId,
        ]);

        ExpenseLine::create([
            'expense_id' => $expense->id,
            'description' => $claim->description ?? ('Employee claim ' . $claim->claim_number),
            'quantity' => 1,
            'unit_price' => $claim->amount,
            'discount' => 0,
            'tax_rate' => 0,
            'amount' => $claim->amount,
            'tax_amount' => 0,
            'line_total' => $claim->amount,
            'expense_account_id' => $expenseAccountId,
            'cost_center_id' => $claim->cost_center_id,
        ]);

        $this->updateExpenseAmount($expense);

        $this->logExpenseAction($expense, 'created_from_claim', null, [
            'claim_id' => $claim->id,
            'claim_number' => $claim->claim_number,
        ], $userId);

        return $expense;
    }

    protected function recordBudgetCheck(Expense $expense): void
    {
        $companyId = $expense->company_id;
        $lines = $expense->lines()->get();

        if ($lines->isEmpty()) {
            return;
        }

        $budget = $this->budgetCheckService->check(
            $companyId,
            $lines->map(fn (ExpenseLine $line) => [
                'expense_account_id' => $line->expense_account_id,
                'estimated_total' => (float) $line->line_total,
            ])->all(),
            $expense->expense_date->format('Y-m-d')
        );

        $exceededAmount = $budget['status'] === 'exceeded'
            ? collect($budget['accounts'])->where('exceeded', true)->sum('requested')
            : 0;

        $expense->update([
            'budget_check' => $budget['status'],
            'budget_check_amount' => round($exceededAmount, 2),
        ]);
    }

    protected function generateExpenseNumber(int $companyId): string
    {
        try {
            return $this->numberingService->getNextNumber($companyId, 'expense');
        } catch (\RuntimeException $e) {
            $year = (int) date('Y');
            $prefix = 'EXP-' . $year . '-';

            DB::table('companies')->where('id', $companyId)->lockForUpdate();

            $lastExpense = Expense::where('company_id', $companyId)
                ->where('expense_number', 'like', $prefix . '%')
                ->orderByDesc('expense_number')
                ->first();

            $newSequence = $lastExpense
                ? (int) substr($lastExpense->expense_number, strlen($prefix)) + 1
                : 1;

            return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        }
    }

    protected function generateClaimNumber(int $companyId): string
    {
        try {
            return $this->numberingService->getNextNumber($companyId, 'expense_claim');
        } catch (\RuntimeException $e) {
            $year = (int) date('Y');
            $prefix = 'CLM-' . $year . '-';

            DB::table('companies')->where('id', $companyId)->lockForUpdate();

            $lastClaim = ExpenseClaim::where('company_id', $companyId)
                ->where('claim_number', 'like', $prefix . '%')
                ->orderByDesc('claim_number')
                ->first();

            $newSequence = $lastClaim
                ? (int) substr($lastClaim->claim_number, strlen($prefix)) + 1
                : 1;

            return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        }
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
            'department' => $lineData['department'] ?? null,
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

    protected function validateCategory(int $companyId, int $categoryId): void
    {
        $category = ExpenseCategory::where('id', $categoryId)
            ->where('company_id', $companyId)
            ->first();

        if (!$category) {
            throw new InvalidArgumentException("Category ID {$categoryId} not found for this company.");
        }
    }

    protected function validateEmployee(int $companyId, int $employeeId): void
    {
        $employee = \App\Models\Employee::where('id', $employeeId)
            ->where('company_id', $companyId)
            ->first();

        if (!$employee) {
            throw new InvalidArgumentException("Employee ID {$employeeId} not found for this company.");
        }
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

    protected function logExpenseAction($entity, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $entity->company_id,
            'journalable_type' => $entity instanceof ExpenseClaim ? ExpenseClaim::class : Expense::class,
            'journalable_id' => $entity->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
