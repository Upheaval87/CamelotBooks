<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\SalesReceipt;
use App\Models\SalesReceiptLine;
use App\Models\SalesReceiptPayment;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesReceiptService
{
    public function __construct(
        protected SalesPostingService $postingService,
        protected NumberingSequenceService $numberingService,
        protected \App\Services\Accounting\InventoryService $inventoryService,
    ) {}

    public function create(array $data, int $userId): SalesReceipt
    {
        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];

            $receiptNumber = $this->numberingService->getNextNumber(
                $companyId,
                'sales_receipt'
            );

            $currency = $data['currency'] ?? $this->systemCurrency($companyId);

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;
            $totalCOGS = 0;

            $receipt = SalesReceipt::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'receipt_number' => $receiptNumber,
                'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => SalesReceipt::STATUS_DRAFT,
                'currency' => $currency,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $line) {
                $amount = round($line['quantity'] * $line['unit_price'], 2);
                $discount = round($line['discount'] ?? 0, 2);
                $net = $amount - $discount;
                $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);
                $subtotal += $net;
                $taxTotal += $tax;
                $discountTotal += $discount;

                $product = $line['product_id'] ? \App\Models\Product::find($line['product_id']) : null;
                $costOfGoods = 0;
                if ($product && $product->tracked_as_inventory) {
                    $qtyOnHand = $this->inventoryService->getQuantityOnHand($companyId, $product->id, $data['branch_id'] ?? null);
                    $avgCost = \App\Models\InventoryCostLayer::where('company_id', $companyId)
                        ->where('product_id', $product->id)
                        ->where('branch_id', $data['branch_id'] ?? null)
                        ->available()
                        ->avg('unit_cost') ?? 0;
                    $costOfGoods = round(min($line['quantity'], $qtyOnHand) * $avgCost, 2);
                    $totalCOGS += $costOfGoods;
                }

                SalesReceiptLine::create([
                    'sales_receipt_id' => $receipt->id,
                    'product_id' => $line['product_id'] ?? null,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => $discount,
                    'tax_rate' => $line['tax_rate'] ?? 0,
                    'amount' => $net,
                    'tax_amount' => $tax,
                    'line_total' => $net + $tax,
                    'income_account_id' => $line['income_account_id'],
                    'cost_of_goods' => $costOfGoods,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                ]);
            }

            foreach ($data['payments'] ?? [] as $payment) {
                $paymentMethod = \App\Models\PosPaymentMethod::find($payment['payment_method_id']);
                if (!$paymentMethod) {
                    throw new InvalidArgumentException("Invalid payment method ID: {$payment['payment_method_id']}");
                }

                SalesReceiptPayment::create([
                    'sales_receipt_id' => $receipt->id,
                    'payment_method_id' => $payment['payment_method_id'],
                    'amount' => $payment['amount'],
                    'cash_tendered' => $payment['cash_tendered'] ?? null,
                    'change_given' => $payment['change_given'] ?? null,
                    'reference_number' => $payment['reference_number'] ?? null,
                    'account_name' => $payment['account_name'] ?? null,
                    'institution' => $payment['institution'] ?? null,
                    'bank_account_id' => $payment['bank_account_id'] ?? null,
                ]);
            }

            $receipt->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => ($data['invoice_id'] ?? null)
                    ? round(array_sum(collect($data['payments'] ?? [])->pluck('amount')->all()), 2)
                    : ($subtotal + $taxTotal),
            ]);

            $this->logReceiptAction($receipt, 'created', null, $receipt->toArray(), $userId);

            return $receipt->fresh(['lines', 'payments']);
        });
    }

    /**
     * Build the posting context (lines + payments) for a receipt so the post-page
     * journal preview and the actual posting always agree.
     *
     * @return array{lines: array, payments: array}
     */
    public function buildPostContext(SalesReceipt $receipt): array
    {
        $payments = $receipt->payments->map(function ($p) {
            $pm = $p->paymentMethod;
            return [
                'amount' => (float) $p->amount,
                'payment_method_name' => $pm->name,
                'clearing_account_id' => $pm->clearing_account_id,
                'bank_account_id' => $p->bank_account_id,
            ];
        })->toArray();

        $lines = $receipt->lines->map(fn($l) => [
            'product_name' => $l->description,
            'income_account_id' => $l->income_account_id,
            'line_total' => (float) $l->line_total,
            'tax_amount' => (float) $l->tax_amount,
            'cost_of_goods' => (float) $l->cost_of_goods,
            'tracked_as_inventory' => $l->cost_of_goods > 0,
        ])->toArray();

        return compact('lines', 'payments');
    }

    public function update(SalesReceipt $receipt, array $data, int $userId): SalesReceipt
    {
        if ($receipt->status !== SalesReceipt::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft receipts can be updated.');
        }

        return DB::transaction(function () use ($receipt, $data, $userId) {
            $oldValues = $receipt->toArray();

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            $receipt->update([
                'branch_id' => $data['branch_id'] ?? $receipt->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $receipt->cost_center_id,
                'customer_id' => array_key_exists('customer_id', $data) ? $data['customer_id'] : $receipt->customer_id,
                'invoice_id' => array_key_exists('invoice_id', $data) ? $data['invoice_id'] : $receipt->invoice_id,
                'receipt_date' => $data['receipt_date'] ?? $receipt->receipt_date,
                'reference' => $data['reference'] ?? $receipt->reference,
                'memo' => $data['memo'] ?? $receipt->memo,
                'currency' => $data['currency'] ?? $receipt->currency,
            ]);

            $this->recreateLines($receipt, $data['lines'], $subtotal, $taxTotal, $discountTotal);

            $receipt->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $receipt->invoice_id
                    ? round(collect($data['payments'] ?? [])->sum('amount'), 2)
                    : ($subtotal + $taxTotal),
            ]);

            $this->logReceiptAction($receipt, 'updated', $oldValues, $receipt->toArray(), $userId);

            return $receipt->fresh(['lines', 'payments']);
        });
    }

    public function destroy(SalesReceipt $receipt, int $userId): void
    {
        if ($receipt->status !== SalesReceipt::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft receipts can be deleted.');
        }

        DB::transaction(function () use ($receipt, $userId) {
            $this->logReceiptAction($receipt, 'deleted', $receipt->toArray(), null, $userId);

            $receipt->payments()->delete();
            $receipt->lines()->delete();
            $receipt->delete();
        });
    }

    public function post(SalesReceipt $receipt, int $userId): SalesReceipt
    {
        if ($receipt->status !== SalesReceipt::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft receipts can be posted.');
        }

        $totalPayments = $receipt->payments->sum('amount');
        if (abs($totalPayments - $receipt->total) > 0.01) {
            throw new InvalidArgumentException("Payment total ({$totalPayments}) does not match receipt total ({$receipt->total}).");
        }

        DB::beginTransaction();

        try {
            // 1. Post journal entry.
            // Settlement receipts (linked to an invoice) reduce the customer's
            // Accounts Receivable (the invoice already recognized revenue).
            // Standalone receipts recognize revenue/tax/COGS as before.
            $je = $this->postToLedger($receipt, $userId);

            // 2. Consume inventory (standalone receipts only — a settlement
            //    receipt never moves inventory).
            if (!$receipt->invoice_id) {
                foreach ($receipt->lines as $line) {
                    if ($line->product && $line->product->tracked_as_inventory && $line->quantity > 0) {
                        $this->inventoryService->consumeStock(
                            $receipt->company_id,
                            $line->product_id,
                            $receipt->branch_id,
                            $line->quantity,
                            $receipt->receipt_date->format('Y-m-d')
                        );
                    }
                }
            }

            // 3. Update receipt
            $receipt->update([
                'status' => SalesReceipt::STATUS_POSTED,
                'journal_entry_id' => $je->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logReceiptAction($receipt, 'posted', null, $receipt->toArray(), $userId);

            DB::commit();
            return $receipt->fresh(['journalEntry', 'lines', 'payments', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Post a receipt to the ledger.
     *
     * Standalone receipt  -> Dr clearing/bank (payments) · Cr revenue per line,
     *                         Cr tax, COGS/Inventory (existing SalesPostingService).
     * Settlement receipt   -> Dr clearing/bank (payments) · Cr AR 1100 (applied).
     *                         Inserts invoice_allocations, updates the invoice's
     *                         amount_paid/settled/status. Overpayment handled per
     *                         config (cap by default, customer-credit opt-in).
     */
    protected function postToLedger(SalesReceipt $receipt, int $userId): \App\Models\JournalEntry
    {
        if ($receipt->invoice_id) {
            return $this->postSettlement($receipt, $userId);
        }

        $context = $this->buildPostContext($receipt);

        return $this->postingService->postSale([
            'company_id' => $receipt->company_id,
            'user_id' => $userId,
            'source_module' => 'sales_receipt',
            'document_number' => $receipt->receipt_number,
            'date' => $receipt->receipt_date->format('Y-m-d'),
            'memo' => $receipt->memo ?? "Sales Receipt {$receipt->receipt_number}",
            'lines' => $context['lines'],
            'payments' => $context['payments'],
        ]);
    }

    /**
     * Post a settlement receipt: Dr clearing/bank per payment, Cr AR for the
     * applied amount. The applied amount per payment is capped at the invoice's
     * outstanding balance (or routed to a customer-credit liability per config).
     */
    protected function postSettlement(SalesReceipt $receipt, int $userId): \App\Models\JournalEntry
    {
        $companyId = $receipt->company_id;

        $jeLines = $this->buildSettlementLines($receipt, $appliedTotal, $excess, $customerCreditAccount);

        $je = $this->postingEngine()->post([
            'company_id' => $companyId,
            'date' => $receipt->receipt_date->format('Y-m-d'),
            'reference' => "RCT-{$receipt->receipt_number}",
            'memo' => $receipt->memo ?? "Sales Receipt {$receipt->receipt_number}",
            'lines' => $jeLines,
            'created_by' => $userId,
            'source_module' => 'sales_receipt',
        ]);

        // Insert allocations (best-effort per payment; capped values row).
        foreach ($this->computeAllocations($receipt) as $allocation) {
            if ($allocation['applied_amount'] > 0) {
                \App\Models\InvoiceAllocation::create($allocation);
            }
        }

        // Update the invoice.
        $invoice = \App\Models\Invoice::query()->whereKey($receipt->invoice_id)->first();
        $newAmountPaid = round((float) $invoice->amount_paid + $appliedTotal, 2);
        $newStatus = $newAmountPaid >= (float) $invoice->amount
            ? \App\Models\Invoice::STATUS_PAID
            : \App\Models\Invoice::STATUS_PARTIALLY_PAID;
        $invoice->update([
            'amount_paid' => $newAmountPaid,
            'settled' => round((float) $invoice->settled + $appliedTotal, 2),
            'status' => $newStatus,
        ]);

        $this->auditInvoiceSettlement($receipt, $invoice, $appliedTotal, $excess, (bool) $customerCreditAccount, $userId);

        return $je;
    }

    /**
     * Preview the settlement journal lines for the post-page, identical to the
     * lines the actual posting will write. Pure — nothing is persisted.
     */
    public function previewSettlementLines(SalesReceipt $receipt): array
    {
        return $this->buildSettlementLines($receipt, $appliedTotal, $excess, $customerCreditAccount);
    }

    /**
     * Build (and return) the settlement journal lines. Also populates the
     * applied/excess totals and the customer-credit account (if used) by
     * reference, so the caller can persist allocations + update the invoice.
     */
    protected function buildSettlementLines(SalesReceipt $receipt, &$appliedTotal, &$excess, &$customerCreditAccount): array
    {
        $companyId = $receipt->company_id;

        $arAccount = $this->resolveAccountByMapping($companyId, 'accounts_receivable', '1100');
        if (!$arAccount) {
            throw new InvalidArgumentException('Accounts Receivable account could not be resolved.');
        }

        $customerCreditAccount = null;
        $policy = config('sales_receipts.overpayment_policy', 'cap');
        if ($policy === 'customer_credit') {
            $customerCreditAccount = $this->resolveAccountByMapping($companyId, 'customer_credit', config('sales_receipts.customer_credit_account_code', '2200'));
        }

        $payments = $receipt->payments;
        $totalPaid = $payments->sum('amount');
        $remaining = 0;
        $invoice = \App\Models\Invoice::query()->whereKey($receipt->invoice_id)->first();
        if ($invoice) {
            $remaining = round((float) $invoice->amount - (float) $invoice->amount_paid, 2);
            if ($remaining < 0) {
                $remaining = 0;
            }
        }

        // Compute applied amounts (capped at the outstanding balance).
        $leftToApply = $remaining;
        $appliedTotal = 0;
        foreach ($payments as $payment) {
            $applied = round(min((float) $payment->amount, $leftToApply), 2);
            if ($applied < 0) {
                $applied = 0;
            }
            $leftToApply = round($leftToApply - $applied, 2);
            $appliedTotal = round($appliedTotal + $applied, 2);
        }
        $excess = round($totalPaid - $appliedTotal, 2);

        $customerCreditUsed = $customerCreditAccount && $excess > 0;

        $jeLines = [];
        foreach ($payments as $payment) {
            $pm = $payment->paymentMethod;
            $accountId = $payment->bank_account_id ?? $pm->clearing_account_id ?? null;
            if (!$accountId) {
                throw new InvalidArgumentException("Payment method '{$pm->name}' has no target account.");
            }
            $label = $payment->bank_account_id ? 'Bank Transfer' : $pm->name;
            $this->accumulateDebit($jeLines, $accountId, (float) $payment->amount, "{$receipt->receipt_number} – {$label}");
        }

        if ($appliedTotal > 0) {
            $this->accumulateCredit($jeLines, $arAccount->id, $appliedTotal, "{$receipt->receipt_number} – Applied to Invoice " . ($invoice->invoice_number ?? ''));
        }
        if ($customerCreditUsed && $excess > 0) {
            $this->accumulateCredit($jeLines, $customerCreditAccount->id, $excess, "{$receipt->receipt_number} – Overpayment credit");
        }

        $totalDebits = round(array_sum(array_map(fn($l) => $l['debit'], $jeLines)), 2);
        $totalCredits = round(array_sum(array_map(fn($l) => $l['credit'], $jeLines)), 2);
        $diff = round($totalDebits - $totalCredits, 2);
        if (abs($diff) > 0.001) {
            if ($customerCreditAccount && $diff > 0) {
                $this->accumulateCredit($jeLines, $customerCreditAccount->id, $diff, "{$receipt->receipt_number} – Overpayment credit");
            } else {
                throw new InvalidArgumentException("Receipt cannot be posted out of balance (difference {$diff}).");
            }
        }

        return $jeLines;
    }

    /**
     * Per-payment applied amounts (capped at the outstanding balance).
     */
    protected function computeAllocations(SalesReceipt $receipt): array
    {
        $remaining = 0;
        $invoice = \App\Models\Invoice::query()->whereKey($receipt->invoice_id)->first();
        if ($invoice) {
            $remaining = round((float) $invoice->amount - (float) $invoice->amount_paid, 2);
            if ($remaining < 0) {
                $remaining = 0;
            }
        }

        $allocations = [];
        $leftToApply = $remaining;
        foreach ($receipt->payments as $payment) {
            $applied = round(min((float) $payment->amount, $leftToApply), 2);
            if ($applied < 0) {
                $applied = 0;
            }
            $leftToApply = round($leftToApply - $applied, 2);
            $allocations[] = [
                'invoice_id' => $receipt->invoice_id,
                'receipt_id' => $receipt->id,
                'payment_id' => $payment->id,
                'applied_amount' => $applied,
            ];
        }

        return $allocations;
    }

    public function void(SalesReceipt $receipt, string $reason, int $userId): SalesReceipt
    {
        if ($receipt->status !== SalesReceipt::STATUS_POSTED) {
            throw new InvalidArgumentException('Only posted receipts can be voided.');
        }

        DB::beginTransaction();

        try {
            // 1. Reverse journal entry
            if ($receipt->journal_entry_id) {
                $this->postingEngine()->reverse($receipt->journal_entry_id, $userId);
            }

            // 1b. Reverse allocations + restore the linked invoice (if any).
            if ($receipt->invoice_id) {
                $this->reverseAllocations($receipt);
            }

            // 2. Return inventory (standalone only)
            if (!$receipt->invoice_id) {
                foreach ($receipt->lines as $line) {
                    if ($line->product && $line->product->tracked_as_inventory && $line->quantity > 0) {
                        $this->inventoryService->receiveStock(
                            $receipt->company_id,
                            $line->product_id,
                            $receipt->branch_id,
                            $line->quantity,
                            $line->quantity > 0 ? $line->cost_of_goods / $line->quantity : 0,
                            'sales_receipt_void',
                            $receipt->id,
                            $receipt->receipt_date->format('Y-m-d')
                        );
                    }
                }
            }

            // 3. Mark voided
            $receipt->update([
                'status' => SalesReceipt::STATUS_VOIDED,
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $this->logReceiptAction($receipt, 'voided', null, $receipt->toArray(), $userId);

            DB::commit();
            return $receipt;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse this receipt's invoice allocations and restore the linked
     * invoice's amount_paid/settled/status. Invoked on void.
     */
    protected function reverseAllocations(SalesReceipt $receipt): void
    {
        $allocations = $receipt->allocations()->get();
        if ($allocations->isEmpty()) {
            return;
        }

        $invoice = \App\Models\Invoice::query()->whereKey($receipt->invoice_id)->first();
        if ($invoice) {
            $reversedTotal = round($allocations->sum('applied_amount'), 2);
            $newAmountPaid = round((float) $invoice->amount_paid - $reversedTotal, 2);
            $invoice->update([
                'amount_paid' => max($newAmountPaid, 0),
                'settled' => max(round((float) $invoice->settled - $reversedTotal, 2), 0),
                'status' => $newAmountPaid <= 0
                    ? \App\Models\Invoice::STATUS_SENT
                    : ($newAmountPaid < (float) $invoice->amount ? \App\Models\Invoice::STATUS_PARTIALLY_PAID : \App\Models\Invoice::STATUS_PAID),
            ]);
        }

        $receipt->allocations()->delete();
    }

    protected function recreateLines(SalesReceipt $receipt, array $lines, float &$subtotal, float &$taxTotal, float &$discountTotal): void
    {
        $receipt->lines()->delete();

        foreach ($lines as $line) {
            $amount = round($line['quantity'] * $line['unit_price'], 2);
            $discount = round($line['discount'] ?? 0, 2);
            $net = $amount - $discount;
            $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);
            $subtotal += $net;
            $taxTotal += $tax;
            $discountTotal += $discount;

            $product = $line['product_id'] ? \App\Models\Product::find($line['product_id']) : null;
            $costOfGoods = 0;
            if ($product && $product->tracked_as_inventory) {
                $qtyOnHand = $this->inventoryService->getQuantityOnHand($receipt->company_id, $product->id, $receipt->branch_id);
                $avgCost = \App\Models\InventoryCostLayer::where('company_id', $receipt->company_id)
                    ->where('product_id', $product->id)
                    ->where('branch_id', $receipt->branch_id)
                    ->available()
                    ->avg('unit_cost') ?? 0;
                $costOfGoods = round(min($line['quantity'], $qtyOnHand) * $avgCost, 2);
            }

            SalesReceiptLine::create([
                'sales_receipt_id' => $receipt->id,
                'product_id' => $line['product_id'] ?? null,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount' => $discount,
                'tax_rate' => $line['tax_rate'] ?? 0,
                'amount' => $net,
                'tax_amount' => $tax,
                'line_total' => $net + $tax,
                'income_account_id' => $line['income_account_id'],
                'cost_of_goods' => $costOfGoods,
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ]);
        }
    }

    protected function recreatePayments(SalesReceipt $receipt, array $payments): void
    {
        $receipt->payments()->delete();

        foreach ($payments as $payment) {
            $paymentMethod = \App\Models\PosPaymentMethod::find($payment['payment_method_id']);
            if (!$paymentMethod) {
                throw new InvalidArgumentException("Invalid payment method ID: {$payment['payment_method_id']}");
            }

            SalesReceiptPayment::create([
                'sales_receipt_id' => $receipt->id,
                'payment_method_id' => $payment['payment_method_id'],
                'amount' => $payment['amount'],
                'cash_tendered' => $payment['cash_tendered'] ?? null,
                'change_given' => $payment['change_given'] ?? null,
                'reference_number' => $payment['reference_number'] ?? null,
                'account_name' => $payment['account_name'] ?? null,
                'institution' => $payment['institution'] ?? null,
                'bank_account_id' => $payment['bank_account_id'] ?? null,
            ]);
        }
    }

    protected function logReceiptAction(SalesReceipt $receipt, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AuditLog::log(
            $receipt->company_id,
            $userId,
            SalesReceipt::class,
            $receipt->id,
            $action,
            $oldValues,
            $newValues,
            $action === 'created' ? "Sales Receipt {$receipt->receipt_number}" : null
        );
    }

    protected function postingEngine(): JournalPostingEngine
    {
        return app(JournalPostingEngine::class);
    }

    /**
     * Resolve the system currency — never hard-coded. Uses the company's
     * base currency, falling back to the 'USD' legacy default only when no
     * company context exists.
     */
    protected function systemCurrency(int $companyId): string
    {
        $company = \App\Models\Company::find($companyId);
        return $company?->base_currency ?: 'USD';
    }

    protected function resolveAccountByMapping(int $companyId, string $mappingKey, string $fallbackCode): ?Account
    {
        $account = \App\Models\DefaultAccountMapping::getAccount($companyId, $mappingKey);
        if ($account) {
            return $account;
        }
        return Account::where('company_id', $companyId)
            ->where('code', $fallbackCode)
            ->where('is_active', true)
            ->first();
    }

    protected function accumulateDebit(array &$lines, int $accountId, float $amount, string $description): void
    {
        foreach ($lines as &$line) {
            if ($line['account_id'] === $accountId && $line['debit'] > 0) {
                $line['debit'] = round($line['debit'] + $amount, 2);
                return;
            }
        }
        $lines[] = ['account_id' => $accountId, 'debit' => round($amount, 2), 'credit' => 0, 'description' => $description];
    }

    protected function accumulateCredit(array &$lines, int $accountId, float $amount, string $description): void
    {
        foreach ($lines as &$line) {
            if ($line['account_id'] === $accountId && $line['credit'] > 0) {
                $line['credit'] = round($line['credit'] + $amount, 2);
                return;
            }
        }
        $lines[] = ['account_id' => $accountId, 'debit' => 0, 'credit' => round($amount, 2), 'description' => $description];
    }

    protected function auditInvoiceSettlement(SalesReceipt $receipt, \App\Models\Invoice $invoice, float $applied, float $excess, bool $customerCreditUsed, int $userId): void
    {
        \App\Models\AuditLog::log(
            $receipt->company_id,
            $userId,
            \App\Models\Invoice::class,
            $invoice->id,
            'settled',
            null,
            [
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'applied_amount' => $applied,
                'excess' => $excess,
                'customer_credit' => $customerCreditUsed,
                'amount_paid' => (float) $invoice->amount_paid,
                'status' => $invoice->status,
            ],
            "Invoice {$invoice->invoice_number} settled via receipt {$receipt->receipt_number}"
        );
    }
}
