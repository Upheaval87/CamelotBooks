<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Customer;
use App\Models\DefaultAccountMapping;
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

        // 5.1 — `customer_id` is nullable so a walk-in invoice can be raised
        // without pre-registering a customer (R12). Only an explicit id is
        // validated; the AR service is responsible for materialising a customer
        // row when the caller supplied one.
        if (!empty($data['customer_id'])) {
            $this->validateCustomer($companyId, $data['customer_id']);
        }

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one invoice line is required.');
        }

        $arAccount = DefaultAccountMapping::getAccount($companyId, 'accounts_receivable');

        return DB::transaction(function () use ($data, $userId, $companyId, $arAccount) {
            $invoiceNumber = $this->generateInvoiceNumber($companyId);

            $invoice = Invoice::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                // `issue_date` is the AR-facing name and `invoice_date` the
                // legacy one; they are two columns for one fact, so both are
                // written from the SAME resolved value (see `resolveIssueDate`).
                'invoice_date' => $this->resolveIssueDate($data),
                'due_date' => $data['due_date'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => Invoice::STATUS_DRAFT,
                'amount' => 0,
                'amount_paid' => 0,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ] + $this->arColumns($data, $invoiceNumber));

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

        if (!empty($data['customer_id'])) {
            $this->validateCustomer($companyId, $data['customer_id']);
        }

        return DB::transaction(function () use ($invoice, $data, $userId, $companyId) {
            $oldValues = $invoice->toArray();

            $invoice->update([
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'invoice_date' => $data['invoice_date'] ?? $data['issue_date'] ?? $invoice->invoice_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'reference' => $data['reference'] ?? $invoice->reference,
                'memo' => $data['memo'] ?? $invoice->memo,
                'branch_id' => $data['branch_id'] ?? $invoice->branch_id,
                'currency' => $data['currency'] ?? $invoice->currency,
            ] + $this->arColumns($data, $invoice->invoice_number));

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
        $arAccount = $this->resolveAccount($companyId, 'accounts_receivable', '1100');
        $taxPayableAccount = DefaultAccountMapping::getAccount($companyId, 'tax_payable');

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
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalDebit += $line->line_total;

                $jeLines[] = [
                    'account_id' => $line->income_account_id,
                    'debit' => 0,
                    'credit' => $line->amount,
                    'memo' => "Invoice {$invoice->invoice_number} - {$line->description}",
                    'entity_type' => Invoice::class,
                    'entity_id' => $invoice->id,
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalCredit += $line->amount;

                if ($line->tax_amount > 0) {
                    if (!$taxPayableAccount) {
                        $taxPayableAccount = $this->resolveAccount($companyId, 'tax_payable', '2300');
                    }
                    $jeLines[] = [
                        'account_id' => $taxPayableAccount->id,
                        'debit' => 0,
                        'credit' => $line->tax_amount,
                        'memo' => "Invoice {$invoice->invoice_number} - Tax - {$line->description}",
                        'entity_type' => Invoice::class,
                        'entity_id' => $invoice->id,
                        'cost_center_id' => $line->cost_center_id,
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
                                'cost_center_id' => $line->cost_center_id,
                            ];
                            $totalDebit += $totalCogs;

                            $jeLines[] = [
                                'account_id' => $invAssetAccount->id,
                                'debit' => 0,
                                'credit' => $totalCogs,
                                'memo' => "Invoice {$invoice->invoice_number} - Inventory - {$line->description}",
                                'entity_type' => Invoice::class,
                                'entity_id' => $invoice->id,
                                'cost_center_id' => $line->cost_center_id,
                            ];
                            $totalCredit += $totalCogs;
                        }
                    }
                }
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

            $this->fxService->postInvoiceInForeignCurrency($invoice, $jeLines, $userId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $invoice->invoice_date->format('Y-m-d'),
                'source_module' => 'invoice',
                'reference' => $invoice->invoice_number,
                'memo' => "Sales invoice {$invoice->invoice_number}",
                'branch_id' => $invoice->branch_id,
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
        $totals = InvoiceTotals::fromLines(
            $invoice->lines()->get(['amount', 'tax_amount', 'line_total'])
        );

        // `amount` stays the legacy accumulator every other module sums against;
        // the AR module (5.1) additionally persists the subtotal/tax/grand
        // breakdown so the register, queue and print sheet never have to
        // re-derive it from the lines.
        $invoice->update([
            'amount' => $totals['grand_total'],
            'subtotal' => $totals['subtotal'],
            'tax_total' => $totals['tax_total'],
            'grand_total' => $totals['grand_total'],
        ]);
    }

    /**
     * INVOICES MODULE 5.1 — the AR-facing columns the legacy service does not
     * know about.
     *
     * Three rules are enforced here so a caller cannot desynchronise the row:
     *
     * 1. `issue_date` and `invoice_date` are written together. The spec names
     *    `issue_date`, but the GL period filter and inventory posting both read
     *    `invoice_date`, so letting them drift would restate a posted document's
     *    ledger period.
     * 2. The money columns (`subtotal`, `tax_total`, `grand_total`) are NOT
     *    accepted here — they are derived from the lines by
     *    `updateInvoiceFromPost()` and must not be client-settable.
     * 3. Only keys actually present in the payload are written, so a legacy
     *    create/update that sends none of them leaves existing values intact.
     *
     * @return array<string, mixed>
     */
    /**
     * `issue_date` and `invoice_date` are two columns for one fact, so whichever
     * name the caller used, both are written from a single resolved value.
     *
     * This exists because the call sites merge the base attribute array with
     * `arColumns()` using PHP's `+`, and `+` keeps the LEFT operand's keys — a
     * second `invoice_date` from `arColumns()` would be silently discarded.
     * Resolving the date before the merge is the only place that cannot drift.
     */
    protected function resolveIssueDate(array $data): ?string
    {
        return $data['invoice_date'] ?? $data['issue_date'] ?? null;
    }

    protected function arColumns(array $data, string $invoiceNumber): array
    {
        $columns = array_intersect_key($data, array_flip([
            'customer_name', 'contact_person', 'email', 'phone', 'bill_address',
            'terms_key', 'customer_ref',
            'notes_customer', 'notes_internal', 'cost_centre', 'department',
            'prepared_by',
        ]));

        if (!empty($data['issue_date'])) {
            $columns['issue_date'] = $data['issue_date'];
            $columns['invoice_date'] = $data['issue_date'];
        }

        // `source_*` is tri-state: absent leaves the stored value, an explicit
        // null clears it (used when an operator detaches a converted source).
        foreach (['source_type', 'source_ref', 'source_quotation_id'] as $sourceKey) {
            if (array_key_exists($sourceKey, $data)) {
                $columns[$sourceKey] = $data[$sourceKey] ?: null;
            }
        }

        return $columns + ['invoice_number' => $invoiceNumber];
    }

    /**
     * INVOICES MODULE 5.1 — delegated to `InvoiceTotals` so the register totals
     * and the posted ledger agree on the arithmetic by construction.
     */
    public function computeLineTotals(array $lineData): array
    {
        return InvoiceTotals::forLine($lineData);
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
            // 8.3: a line whose description is left blank still counts as
            // complete, so the column is nullable and defaults to an empty
            // string rather than being required at the model boundary.
            'description' => (string) ($lineData['description'] ?? ''),
            // The AR module (5.1) snapshots the catalogue label onto the line so
            // a later product rename cannot rewrite historical documents.
            'position' => $lineData['position'] ?? null,
            'item_code' => $lineData['item_code'] ?? null,
            'item_name' => $lineData['item_name'] ?? null,
            'quantity' => $lineData['quantity'] ?? 1,
            'unit_price' => $lineData['unit_price'],
            'discount' => $lineData['discount'] ?? 0,
            'tax_rate' => $lineData['tax_rate'] ?? 0,
            'amount' => $totals['amount'],
            'tax_amount' => $totals['tax_amount'],
            'line_total' => $totals['line_total'],
            'income_account_id' => $lineData['income_account_id'],
            'cost_center_id' => $lineData['cost_center_id'] ?? null,
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

    /**
     * Resolve a default account by mapping key, falling back to the seeded
     * chart-of-accounts code when no explicit mapping exists. Mirrors
     * BillService/TaxEngine so a missing mapping yields a clear error rather
     * than a null-property fatal during posting.
     */
    protected function resolveAccount(int $companyId, string $mappingKey, string $fallbackCode): Account
    {
        $account = DefaultAccountMapping::getAccount($companyId, $mappingKey);

        if (!$account) {
            $account = Account::where('company_id', $companyId)
                ->where('code', $fallbackCode)
                ->first();
        }

        if (!$account) {
            throw new InvalidArgumentException(
                "Account for mapping '{$mappingKey}' (fallback code {$fallbackCode}) not found for this company."
            );
        }

        return $account;
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
