<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function create(array $data, int $userId): Bill
    {
        $companyId = $data['company_id'];

        $this->validateVendor($companyId, $data['vendor_id']);

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one bill line is required.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $billNumber = $this->generateBillNumber($companyId);

            $bill = Bill::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'bill_number' => $billNumber,
                'internal_number' => $data['internal_number'] ?? null,
                'bill_date' => $data['bill_date'],
                'due_date' => $data['due_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => Bill::STATUS_DRAFT,
                'amount' => 0,
                'amount_paid' => 0,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->createLine($bill, $lineData, $companyId);
            }

            $this->updateBillAmount($bill);

            $this->logBillAction($bill, 'created', null, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function update(Bill $bill, array $data, int $userId): Bill
    {
        if ($bill->status !== Bill::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft bills can be updated.');
        }

        $companyId = $bill->company_id;

        if (isset($data['vendor_id'])) {
            $this->validateVendor($companyId, $data['vendor_id']);
        }

        return DB::transaction(function () use ($bill, $data, $userId, $companyId) {
            $oldValues = $bill->toArray();

            $bill->update([
                'vendor_id' => $data['vendor_id'] ?? $bill->vendor_id,
                'bill_date' => $data['bill_date'] ?? $bill->bill_date,
                'due_date' => $data['due_date'] ?? $bill->due_date,
                'internal_number' => $data['internal_number'] ?? $bill->internal_number,
                'reference' => $data['reference'] ?? $bill->reference,
                'memo' => $data['memo'] ?? $bill->memo,
                'branch_id' => $data['branch_id'] ?? $bill->branch_id,
                'currency' => $data['currency'] ?? $bill->currency,
            ]);

            if (isset($data['lines'])) {
                $bill->lines()->delete();

                foreach ($data['lines'] as $lineData) {
                    $this->createLine($bill, $lineData, $companyId);
                }
            }

            $this->updateBillAmount($bill);

            $this->logBillAction($bill, 'updated', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function post(Bill $bill, int $userId): Bill
    {
        if (!in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_PENDING_APPROVAL])) {
            throw new InvalidArgumentException('Only draft or pending approval bills can be posted.');
        }

        $companyId = $bill->company_id;
        $apAccount = $this->findAccountByCode($companyId, '2000');
        $taxReceivableAccount = $this->findAccountByCode($companyId, '1150');

        return DB::transaction(function () use ($bill, $userId, $companyId, $apAccount, $taxReceivableAccount) {
            $oldValues = $bill->toArray();

            $lines = $bill->lines()->get();
            $jeLines = [];

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $jeLines[] = [
                    'account_id' => $line->expense_account_id,
                    'debit' => $line->amount,
                    'credit' => 0,
                    'memo' => "Bill {$bill->bill_number} - {$line->description}",
                    'entity_type' => Bill::class,
                    'entity_id' => $bill->id,
                ];
                $totalDebit += $line->amount;

                if ($line->tax_amount > 0) {
                    $jeLines[] = [
                        'account_id' => $taxReceivableAccount->id,
                        'debit' => $line->tax_amount,
                        'credit' => 0,
                        'memo' => "Bill {$bill->bill_number} - Tax - {$line->description}",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                    ];
                    $totalDebit += $line->tax_amount;
                }

                $jeLines[] = [
                    'account_id' => $apAccount->id,
                    'debit' => 0,
                    'credit' => $line->line_total,
                    'memo' => "Bill {$bill->bill_number} - {$line->description}",
                    'entity_type' => Bill::class,
                    'entity_id' => $bill->id,
                ];
                $totalCredit += $line->line_total;
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new InvalidArgumentException(
                    "Journal entry does not balance. Debit: " . number_format($totalDebit, 2) .
                    ", Credit: " . number_format($totalCredit, 2)
                );
            }

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $bill->bill_date->format('Y-m-d'),
                'source_module' => 'bill',
                'reference' => $bill->bill_number,
                'memo' => "Vendor bill {$bill->bill_number}",
                'lines' => $jeLines,
            ]);

            $bill->update([
                'status' => Bill::STATUS_APPROVED,
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logBillAction($bill, 'posted', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function approve(Bill $bill, int $userId): Bill
    {
        if ($bill->status !== Bill::STATUS_PENDING_APPROVAL) {
            throw new InvalidArgumentException('Only bills pending approval can be approved.');
        }

        return DB::transaction(function () use ($bill, $userId) {
            $oldValues = $bill->toArray();

            $bill->update([
                'status' => Bill::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            if (!$bill->journal_entry_id) {
                $companyId = $bill->company_id;
                $apAccount = $this->findAccountByCode($companyId, '2000');
                $taxReceivableAccount = $this->findAccountByCode($companyId, '1150');

                $lines = $bill->lines()->get();
                $jeLines = [];

                foreach ($lines as $line) {
                    $jeLines[] = [
                        'account_id' => $line->expense_account_id,
                        'debit' => $line->amount,
                        'credit' => 0,
                        'memo' => "Bill {$bill->bill_number} - {$line->description}",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                    ];

                    if ($line->tax_amount > 0) {
                        $jeLines[] = [
                            'account_id' => $taxReceivableAccount->id,
                            'debit' => $line->tax_amount,
                            'credit' => 0,
                            'memo' => "Bill {$bill->bill_number} - Tax - {$line->description}",
                            'entity_type' => Bill::class,
                            'entity_id' => $bill->id,
                        ];
                    }

                    $jeLines[] = [
                        'account_id' => $apAccount->id,
                        'debit' => 0,
                        'credit' => $line->line_total,
                        'memo' => "Bill {$bill->bill_number} - {$line->description}",
                        'entity_type' => Bill::class,
                        'entity_id' => $bill->id,
                    ];
                }

                $journalEntry = $this->postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $bill->bill_date->format('Y-m-d'),
                    'source_module' => 'bill',
                    'reference' => $bill->bill_number,
                    'memo' => "Vendor bill {$bill->bill_number}",
                    'lines' => $jeLines,
                ]);

                $bill->update([
                    'journal_entry_id' => $journalEntry->id,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                ]);
            }

            $this->logBillAction($bill, 'approved', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function void(Bill $bill, string $reason, int $userId): Bill
    {
        if ($bill->status === Bill::STATUS_VOID) {
            throw new InvalidArgumentException('This bill is already voided.');
        }

        if ($bill->status === Bill::STATUS_DRAFT) {
            throw new InvalidArgumentException('Draft bills cannot be voided. Delete them instead.');
        }

        if (!$bill->journal_entry_id) {
            throw new InvalidArgumentException('This bill has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($bill, $reason, $userId) {
            $oldValues = $bill->toArray();

            $this->postingEngine->reverse($bill->journal_entry_id, $userId);

            $bill->update([
                'status' => Bill::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logBillAction($bill, 'voided', $oldValues, $bill->toArray(), $userId);

            return $bill;
        });
    }

    public function updateBillAmount(Bill $bill): void
    {
        $total = (float) $bill->lines()->sum('line_total');

        $bill->update(['amount' => round($total, 2)]);
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

    protected function createLine(Bill $bill, array $lineData, int $companyId): BillLine
    {
        if (isset($lineData['product_id'])) {
            $this->validateProduct($companyId, $lineData['product_id']);
        }

        $this->validateAccount($companyId, $lineData['expense_account_id']);

        $totals = $this->computeLineTotals($lineData);

        return BillLine::create([
            'bill_id' => $bill->id,
            'product_id' => $lineData['product_id'] ?? null,
            'description' => $lineData['description'],
            'quantity' => $lineData['quantity'] ?? 1,
            'unit_price' => $lineData['unit_price'],
            'discount' => $lineData['discount'] ?? 0,
            'tax_rate' => $lineData['tax_rate'] ?? 0,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'line_total' => $totals['line_total'],
            'expense_account_id' => $lineData['expense_account_id'],
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
        $product = Product::where('id', $productId)
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

    protected function generateBillNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'BILL-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastBill = Bill::where('company_id', $companyId)
            ->where('bill_number', 'like', $prefix . '%')
            ->orderByDesc('bill_number')
            ->first();

        if ($lastBill) {
            $lastSequence = (int) substr($lastBill->bill_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logBillAction(Bill $bill, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $bill->company_id,
            'journalable_type' => Bill::class,
            'journalable_id' => $bill->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
