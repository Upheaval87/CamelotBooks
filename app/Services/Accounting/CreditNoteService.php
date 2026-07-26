<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\CreditNote;
use App\Models\CreditNoteAllocation;
use App\Models\CreditNoteLine;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditNoteService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function create(array $data, int $userId): CreditNote
    {
        $companyId = $data['company_id'];

        $this->validateCustomer($companyId, $data['customer_id']);

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one credit note line is required.');
        }

        if (isset($data['invoice_id'])) {
            $this->validateInvoice($companyId, $data['invoice_id']);
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $creditNoteNumber = $this->generateCreditNoteNumber($companyId);

            $creditNote = CreditNote::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'credit_note_number' => $creditNoteNumber,
                'credit_note_date' => $data['credit_note_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => CreditNote::STATUS_DRAFT,
                'amount' => 0,
                'amount_applied' => 0,
                'amount_refunded' => 0,
                'invoice_id' => $data['invoice_id'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->createLine($creditNote, $lineData, $companyId);
            }

            $this->updateCreditNoteAmount($creditNote);

            $this->logCreditNoteAction($creditNote, 'created', null, $creditNote->toArray(), $userId);

            return $creditNote;
        });
    }

    public function post(CreditNote $creditNote, int $userId): CreditNote
    {
        if ($creditNote->status !== CreditNote::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft credit notes can be posted.');
        }

        $companyId = $creditNote->company_id;
        $arAccount = $this->findAccountByCode($companyId, '1100');
        $taxPayableAccount = $this->findAccountByCode($companyId, '2300');

        return DB::transaction(function () use ($creditNote, $userId, $companyId, $arAccount, $taxPayableAccount) {
            $oldValues = $creditNote->toArray();

            $lines = $creditNote->lines()->get();
            $jeLines = [];

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $jeLines[] = [
                    'account_id' => $line->income_account_id,
                    'debit' => $line->amount,
                    'credit' => 0,
                    'memo' => "Credit note {$creditNote->credit_note_number} - {$line->description}",
                    'entity_type' => CreditNote::class,
                    'entity_id' => $creditNote->id,
                ];
                $totalDebit += $line->amount;

                if ($line->tax_amount > 0) {
                    $jeLines[] = [
                        'account_id' => $taxPayableAccount->id,
                        'debit' => $line->tax_amount,
                        'credit' => 0,
                        'memo' => "Credit note {$creditNote->credit_note_number} - Tax - {$line->description}",
                        'entity_type' => CreditNote::class,
                        'entity_id' => $creditNote->id,
                    ];
                    $totalDebit += $line->tax_amount;
                }

                $jeLines[] = [
                    'account_id' => $arAccount->id,
                    'debit' => 0,
                    'credit' => $line->line_total,
                    'memo' => "Credit note {$creditNote->credit_note_number} - {$line->description}",
                    'entity_type' => CreditNote::class,
                    'entity_id' => $creditNote->id,
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
                'date' => $creditNote->credit_note_date->format('Y-m-d'),
                'source_module' => 'credit_note',
                'reference' => $creditNote->credit_note_number,
                'memo' => "Credit note {$creditNote->credit_note_number}",
                'branch_id' => $creditNote->branch_id,
                'lines' => $jeLines,
            ]);

            $creditNote->update([
                'status' => CreditNote::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logCreditNoteAction($creditNote, 'posted', $oldValues, $creditNote->toArray(), $userId);

            return $creditNote;
        });
    }

    public function apply(CreditNote $creditNote, int $invoiceId, float $amount): CreditNote
    {
        if ($creditNote->status !== CreditNote::STATUS_POSTED) {
            throw new InvalidArgumentException('Only posted credit notes can be applied.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Application amount must be greater than zero.');
        }

        $availableAmount = $creditNote->amount - $creditNote->amount_applied;

        if (round($amount, 2) > round($availableAmount, 2)) {
            throw new InvalidArgumentException(
                "Application amount ({$amount}) exceeds available credit note balance ({$availableAmount})."
            );
        }

        $companyId = $creditNote->company_id;

        $invoice = Invoice::where('id', $invoiceId)
            ->where('company_id', $companyId)
            ->first();

        if (!$invoice) {
            throw new InvalidArgumentException("Invoice ID {$invoiceId} not found for this company.");
        }

        if (in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID])) {
            throw new InvalidArgumentException('Cannot apply credit note to a draft or voided invoice.');
        }

        $invoiceBalance = $invoice->amount - $invoice->amount_paid;

        if (round($amount, 2) > round($invoiceBalance, 2)) {
            throw new InvalidArgumentException(
                "Application amount ({$amount}) exceeds invoice balance due ({$invoiceBalance})."
            );
        }

        return DB::transaction(function () use ($creditNote, $invoice, $amount) {
            CreditNoteAllocation::create([
                'credit_note_id' => $creditNote->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

            $invoice->amount_paid = round($invoice->amount_paid + $amount, 2);

            if (round($invoice->amount_paid, 2) >= round($invoice->amount, 2)) {
                $invoice->status = Invoice::STATUS_PAID;
            } else {
                $invoice->status = Invoice::STATUS_PARTIALLY_PAID;
            }

            $invoice->save();

            $creditNote->amount_applied = round($creditNote->amount_applied + $amount, 2);

            if (round($creditNote->amount_applied, 2) >= round($creditNote->amount, 2)) {
                $creditNote->status = CreditNote::STATUS_APPLIED;
            }

            $creditNote->save();

            return $creditNote;
        });
    }

    public function void(CreditNote $creditNote, string $reason, int $userId): CreditNote
    {
        if ($creditNote->status === CreditNote::STATUS_VOID) {
            throw new InvalidArgumentException('This credit note is already voided.');
        }

        if ($creditNote->status === CreditNote::STATUS_DRAFT) {
            throw new InvalidArgumentException('Draft credit notes cannot be voided. Delete them instead.');
        }

        if ($creditNote->status === CreditNote::STATUS_APPLIED) {
            throw new InvalidArgumentException('Cannot void a credit note that has been applied to invoices.');
        }

        if (!$creditNote->journal_entry_id) {
            throw new InvalidArgumentException('This credit note has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($creditNote, $reason, $userId) {
            $oldValues = $creditNote->toArray();

            $this->postingEngine->reverse($creditNote->journal_entry_id, $userId);

            $creditNote->update([
                'status' => CreditNote::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logCreditNoteAction($creditNote, 'voided', $oldValues, $creditNote->toArray(), $userId);

            return $creditNote;
        });
    }

    public function updateCreditNoteAmount(CreditNote $creditNote): void
    {
        $total = (float) $creditNote->lines()->sum('line_total');

        $creditNote->update(['amount' => round($total, 2)]);
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

    protected function createLine(CreditNote $creditNote, array $lineData, int $companyId): CreditNoteLine
    {
        if (isset($lineData['product_id'])) {
            $this->validateProduct($companyId, $lineData['product_id']);
        }

        $this->validateAccount($companyId, $lineData['income_account_id']);

        $totals = $this->computeLineTotals($lineData);

        return CreditNoteLine::create([
            'credit_note_id' => $creditNote->id,
            'product_id' => $lineData['product_id'] ?? null,
            'description' => $lineData['description'],
            'quantity' => $lineData['quantity'] ?? 1,
            'unit_price' => $lineData['unit_price'],
            'discount' => $lineData['discount'] ?? 0,
            'tax_rate' => $lineData['tax_rate'] ?? 0,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'line_total' => $totals['line_total'],
            'income_account_id' => $lineData['income_account_id'],
        ]);
    }

    protected function validateCustomer(int $companyId, int $customerId): void
    {
        $customer = Customer::where('id', $customerId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$customer) {
            throw new InvalidArgumentException("Customer ID {$customerId} not found or inactive for this company.");
        }
    }

    protected function validateInvoice(int $companyId, int $invoiceId): void
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('company_id', $companyId)
            ->first();

        if (!$invoice) {
            throw new InvalidArgumentException("Invoice ID {$invoiceId} not found for this company.");
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
        $prefix = 'CN-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastCreditNote = CreditNote::where('company_id', $companyId)
            ->where('credit_note_number', 'like', $prefix . '%')
            ->orderByDesc('credit_note_number')
            ->first();

        if ($lastCreditNote) {
            $lastSequence = (int) substr($lastCreditNote->credit_note_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logCreditNoteAction(CreditNote $creditNote, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $creditNote->company_id,
            'journalable_type' => CreditNote::class,
            'journalable_id' => $creditNote->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
