<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Bill;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Models\VendorCreditAllocation;
use App\Models\VendorCreditLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VendorCreditService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function create(array $data, int $userId): VendorCredit
    {
        $companyId = $data['company_id'];

        $this->validateVendor($companyId, $data['vendor_id']);

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one vendor credit line is required.');
        }

        if (isset($data['bill_id'])) {
            $this->validateBill($companyId, $data['bill_id']);
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $creditNoteNumber = $this->generateCreditNoteNumber($companyId);

            $vendorCredit = VendorCredit::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'credit_note_number' => $creditNoteNumber,
                'credit_note_date' => $data['credit_note_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => VendorCredit::STATUS_DRAFT,
                'amount' => 0,
                'amount_applied' => 0,
                'amount_refunded' => 0,
                'bill_id' => $data['bill_id'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->createLine($vendorCredit, $lineData, $companyId);
            }

            $this->updateVendorCreditAmount($vendorCredit);

            $this->logAction($vendorCredit, 'created', null, $vendorCredit->toArray(), $userId);

            return $vendorCredit;
        });
    }

    public function post(VendorCredit $vendorCredit, int $userId): VendorCredit
    {
        if ($vendorCredit->status !== VendorCredit::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft vendor credits can be posted.');
        }

        $companyId = $vendorCredit->company_id;
        $apAccount = $this->findAccountByCode($companyId, '2000');
        $taxReceivableAccount = $this->findAccountByCode($companyId, '1150');

        return DB::transaction(function () use ($vendorCredit, $userId, $companyId, $apAccount, $taxReceivableAccount) {
            $oldValues = $vendorCredit->toArray();

            $lines = $vendorCredit->lines()->get();
            $jeLines = [];

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $jeLines[] = [
                    'account_id' => $apAccount->id,
                    'debit' => $line->line_total,
                    'credit' => 0,
                    'memo' => "Vendor credit {$vendorCredit->credit_note_number} - {$line->description}",
                    'entity_type' => VendorCredit::class,
                    'entity_id' => $vendorCredit->id,
                ];
                $totalDebit += $line->line_total;

                $jeLines[] = [
                    'account_id' => $line->expense_account_id,
                    'debit' => 0,
                    'credit' => $line->amount,
                    'memo' => "Vendor credit {$vendorCredit->credit_note_number} - {$line->description}",
                    'entity_type' => VendorCredit::class,
                    'entity_id' => $vendorCredit->id,
                ];
                $totalCredit += $line->amount;

                if ($line->tax_amount > 0) {
                    $jeLines[] = [
                        'account_id' => $taxReceivableAccount->id,
                        'debit' => 0,
                        'credit' => $line->tax_amount,
                        'memo' => "Vendor credit {$vendorCredit->credit_note_number} - Tax - {$line->description}",
                        'entity_type' => VendorCredit::class,
                        'entity_id' => $vendorCredit->id,
                    ];
                    $totalCredit += $line->tax_amount;
                }
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
                'date' => $vendorCredit->credit_note_date->format('Y-m-d'),
                'source_module' => 'vendor_credit',
                'reference' => $vendorCredit->credit_note_number,
                'memo' => "Vendor credit {$vendorCredit->credit_note_number}",
                'lines' => $jeLines,
            ]);

            $vendorCredit->update([
                'status' => VendorCredit::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logAction($vendorCredit, 'posted', $oldValues, $vendorCredit->toArray(), $userId);

            return $vendorCredit;
        });
    }

    public function apply(VendorCredit $vendorCredit, int $billId, float $amount): VendorCredit
    {
        if ($vendorCredit->status !== VendorCredit::STATUS_POSTED) {
            throw new InvalidArgumentException('Only posted vendor credits can be applied.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Application amount must be greater than zero.');
        }

        $availableAmount = $vendorCredit->amount - $vendorCredit->amount_applied;

        if (round($amount, 2) > round($availableAmount, 2)) {
            throw new InvalidArgumentException(
                "Application amount ({$amount}) exceeds available vendor credit balance ({$availableAmount})."
            );
        }

        $companyId = $vendorCredit->company_id;

        $bill = Bill::where('id', $billId)
            ->where('company_id', $companyId)
            ->first();

        if (!$bill) {
            throw new InvalidArgumentException("Bill ID {$billId} not found for this company.");
        }

        if (in_array($bill->status, [Bill::STATUS_DRAFT, Bill::STATUS_VOID])) {
            throw new InvalidArgumentException('Cannot apply vendor credit to a draft or voided bill.');
        }

        $billBalance = $bill->amount - $bill->amount_paid;

        if (round($amount, 2) > round($billBalance, 2)) {
            throw new InvalidArgumentException(
                "Application amount ({$amount}) exceeds bill balance due ({$billBalance})."
            );
        }

        return DB::transaction(function () use ($vendorCredit, $bill, $amount) {
            VendorCreditAllocation::create([
                'vendor_credit_id' => $vendorCredit->id,
                'bill_id' => $bill->id,
                'amount' => $amount,
            ]);

            $bill->amount_paid = round((float) $bill->amount_paid + $amount, 2);

            if (round($bill->amount_paid, 2) >= round($bill->amount, 2)) {
                $bill->status = Bill::STATUS_PAID;
            } else {
                $bill->status = Bill::STATUS_PARTIALLY_PAID;
            }

            $bill->save();

            $vendorCredit->amount_applied = round((float) $vendorCredit->amount_applied + $amount, 2);

            if (round($vendorCredit->amount_applied, 2) >= round($vendorCredit->amount, 2)) {
                $vendorCredit->status = VendorCredit::STATUS_APPLIED;
            }

            $vendorCredit->save();

            return $vendorCredit;
        });
    }

    public function void(VendorCredit $vendorCredit, string $reason, int $userId): VendorCredit
    {
        if ($vendorCredit->status === VendorCredit::STATUS_VOID) {
            throw new InvalidArgumentException('This vendor credit is already voided.');
        }

        if ($vendorCredit->status === VendorCredit::STATUS_DRAFT) {
            throw new InvalidArgumentException('Draft vendor credits cannot be voided. Delete them instead.');
        }

        if ($vendorCredit->status === VendorCredit::STATUS_APPLIED) {
            throw new InvalidArgumentException('Cannot void a vendor credit that has been applied to bills.');
        }

        if (!$vendorCredit->journal_entry_id) {
            throw new InvalidArgumentException('This vendor credit has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($vendorCredit, $reason, $userId) {
            $oldValues = $vendorCredit->toArray();

            $this->postingEngine->reverse($vendorCredit->journal_entry_id, $userId);

            $vendorCredit->update([
                'status' => VendorCredit::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logAction($vendorCredit, 'voided', $oldValues, $vendorCredit->toArray(), $userId);

            return $vendorCredit;
        });
    }

    public function updateVendorCreditAmount(VendorCredit $vendorCredit): void
    {
        $total = (float) $vendorCredit->lines()->sum('line_total');

        $vendorCredit->update(['amount' => round($total, 2)]);
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

    protected function createLine(VendorCredit $vendorCredit, array $lineData, int $companyId): VendorCreditLine
    {
        if (isset($lineData['product_id'])) {
            $this->validateProduct($companyId, $lineData['product_id']);
        }

        $this->validateAccount($companyId, $lineData['expense_account_id']);

        $totals = $this->computeLineTotals($lineData);

        return VendorCreditLine::create([
            'vendor_credit_id' => $vendorCredit->id,
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

    protected function validateBill(int $companyId, int $billId): void
    {
        $bill = Bill::where('id', $billId)
            ->where('company_id', $companyId)
            ->first();

        if (!$bill) {
            throw new InvalidArgumentException("Bill ID {$billId} not found for this company.");
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

    protected function generateCreditNoteNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'VCN-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastCredit = VendorCredit::where('company_id', $companyId)
            ->where('credit_note_number', 'like', $prefix . '%')
            ->orderByDesc('credit_note_number')
            ->first();

        if ($lastCredit) {
            $lastSequence = (int) substr($lastCredit->credit_note_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logAction(VendorCredit $vendorCredit, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $vendorCredit->company_id,
            'journalable_type' => VendorCredit::class,
            'journalable_id' => $vendorCredit->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
