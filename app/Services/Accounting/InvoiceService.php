<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Services\Accounting\ForeignCurrencyService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    protected JournalPostingEngine $postingEngine;
    protected ForeignCurrencyService $fxService;
    protected InventoryService $inventoryService;

    public function __construct(JournalPostingEngine $postingEngine, ForeignCurrencyService $fxService, InventoryService $inventoryService)
    {
        $this->postingEngine = $postingEngine;
        $this->fxService = $fxService;
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data, int $userId): Invoice
    {
        $companyId = $data['company_id'];

        $this->validateCustomer($companyId, $data['customer_id']);

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one invoice line is required.');
        }

        $arAccount = $this->findAccountByCode($companyId, '1100');

        return DB::transaction(function () use ($data, $userId, $companyId, $arAccount) {
            $invoiceNumber = $this->generateInvoiceNumber($companyId);

            $invoice = Invoice::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => Invoice::STATUS_DRAFT,
                'amount' => 0,
                'amount_paid' => 0,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->createLine($invoice, $lineData, $companyId);
            }

            $this->updateInvoiceFromPost($invoice);

            $this->logInvoiceAction($invoice, 'created', null, $invoice->toArray(), $userId);

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $data, int $userId): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft invoices can be updated.');
        }

        $companyId = $invoice->company_id;

        if (isset($data['customer_id'])) {
            $this->validateCustomer($companyId, $data['customer_id']);
        }

        return DB::transaction(function () use ($invoice, $data, $userId, $companyId) {
            $oldValues = $invoice->toArray();

            $invoice->update([
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'reference' => $data['reference'] ?? $invoice->reference,
                'memo' => $data['memo'] ?? $invoice->memo,
                'branch_id' => $data['branch_id'] ?? $invoice->branch_id,
                'currency' => $data['currency'] ?? $invoice->currency,
            ]);

            if (isset($data['lines'])) {
                $invoice->lines()->delete();

                foreach ($data['lines'] as $lineData) {
                    $this->createLine($invoice, $lineData, $companyId);
                }
            }

            $this->updateInvoiceFromPost($invoice);

            $this->logInvoiceAction($invoice, 'updated', $oldValues, $invoice->toArray(), $userId);

            return $invoice;
        });
    }

    public function post(Invoice $invoice, int $userId): Invoice
    {
        if (!in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT])) {
            throw new InvalidArgumentException('Only draft or sent invoices can be posted.');
        }

        $companyId = $invoice->company_id;
        $arAccount = $this->findAccountByCode($companyId, '1100');
        $taxPayableAccount = $this->findAccountByCode($companyId, '2300');

        return DB::transaction(function () use ($invoice, $userId, $companyId, $arAccount, $taxPayableAccount) {
            $oldValues = $invoice->toArray();

            $lines = $invoice->lines()->with('product')->get();
            $jeLines = [];

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $jeLines[] = [
                    'account_id' => $arAccount->id,
                    'debit' => $line->line_total,
                    'credit' => 0,
                    'memo' => "Invoice {$invoice->invoice_number} - {$line->description}",
                    'entity_type' => Invoice::class,
                    'entity_id' => $invoice->id,
                ];
                $totalDebit += $line->line_total;

                $jeLines[] = [
                    'account_id' => $line->income_account_id,
                    'debit' => 0,
                    'credit' => $line->amount,
                    'memo' => "Invoice {$invoice->invoice_number} - {$line->description}",
                    'entity_type' => Invoice::class,
                    'entity_id' => $invoice->id,
                ];
                $totalCredit += $line->amount;

                if ($line->tax_amount > 0) {
                    $jeLines[] = [
                        'account_id' => $taxPayableAccount->id,
                        'debit' => 0,
                        'credit' => $line->tax_amount,
                        'memo' => "Invoice {$invoice->invoice_number} - Tax - {$line->description}",
                        'entity_type' => Invoice::class,
                        'entity_id' => $invoice->id,
                    ];
                    $totalCredit += $line->tax_amount;
                }

                if ($line->product && $line->product->tracked_as_inventory && $line->product_id) {
                    $cogsAccount = $line->product->expenseAccount;
                    $invAssetAccount = $line->product->inventoryAssetAccount;

                    if ($cogsAccount && $invAssetAccount) {
                        $consumedLayers = $this->inventoryService->consumeStock(
                            $companyId,
                            $line->product_id,
                            $invoice->branch_id,
                            (float) $line->quantity,
                            $invoice->invoice_date->format('Y-m-d')
                        );

                        $totalCogs = array_sum(array_column($consumedLayers, 'total_cost'));

                        if ($totalCogs > 0) {
                            $jeLines[] = [
                                'account_id' => $cogsAccount->id,
                                'debit' => $totalCogs,
                                'credit' => 0,
                                'memo' => "Invoice {$invoice->invoice_number} - COGS - {$line->description}",
                                'entity_type' => Invoice::class,
                                'entity_id' => $invoice->id,
                            ];
                            $totalDebit += $totalCogs;

                            $jeLines[] = [
                                'account_id' => $invAssetAccount->id,
                                'debit' => 0,
                                'credit' => $totalCogs,
                                'memo' => "Invoice {$invoice->invoice_number} - Inventory - {$line->description}",
                                'entity_type' => Invoice::class,
                                'entity_id' => $invoice->id,
                            ];
                            $totalCredit += $totalCogs;
                        }
                    }
                }
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new InvalidArgumentException(
                    "Journal entry does not balance. Debit: " . number_format($totalDebit, 2) .
                    ", Credit: " . number_format($totalCredit, 2)
                );
            }

            $this->fxService->postInvoiceInForeignCurrency($invoice, $jeLines, $userId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $invoice->invoice_date->format('Y-m-d'),
                'source_module' => 'invoice',
                'reference' => $invoice->invoice_number,
                'memo' => "Sales invoice {$invoice->invoice_number}",
                'lines' => $jeLines,
            ]);

            $invoice->update([
                'status' => Invoice::STATUS_SENT,
                'journal_entry_id' => $journalEntry->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logInvoiceAction($invoice, 'posted', $oldValues, $invoice->toArray(), $userId);

            return $invoice;
        });
    }

    public function void(Invoice $invoice, string $reason, int $userId): Invoice
    {
        if ($invoice->status === Invoice::STATUS_VOID) {
            throw new InvalidArgumentException('This invoice is already voided.');
        }

        if ($invoice->status === Invoice::STATUS_DRAFT) {
            throw new InvalidArgumentException('Draft invoices cannot be voided. Delete them instead.');
        }

        if (!$invoice->journal_entry_id) {
            throw new InvalidArgumentException('This invoice has no posted journal entry to reverse.');
        }

        return DB::transaction(function () use ($invoice, $reason, $userId) {
            $oldValues = $invoice->toArray();

            $this->postingEngine->reverse($invoice->journal_entry_id, $userId);

            $invoice->update([
                'status' => Invoice::STATUS_VOID,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logInvoiceAction($invoice, 'voided', $oldValues, $invoice->toArray(), $userId);

            return $invoice;
        });
    }

    public function updateInvoiceFromPost(Invoice $invoice): void
    {
        $total = (float) $invoice->lines()->sum('line_total');

        $invoice->update(['amount' => round($total, 2)]);
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

    protected function createLine(Invoice $invoice, array $lineData, int $companyId): InvoiceLine
    {
        if (isset($lineData['product_id'])) {
            $this->validateProduct($companyId, $lineData['product_id']);
        }

        $this->validateAccount($companyId, $lineData['income_account_id']);

        $totals = $this->computeLineTotals($lineData);

        return InvoiceLine::create([
            'invoice_id' => $invoice->id,
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

    protected function generateInvoiceNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'INV-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastInvoice = Invoice::where('company_id', $companyId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logInvoiceAction(Invoice $invoice, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $invoice->company_id,
            'journalable_type' => Invoice::class,
            'journalable_id' => $invoice->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
