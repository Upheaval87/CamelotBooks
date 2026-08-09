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

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;
            $totalCOGS = 0;

            $receipt = SalesReceipt::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'receipt_number' => $receiptNumber,
                'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => SalesReceipt::STATUS_DRAFT,
                'currency' => $data['currency'] ?? 'USD',
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
                'total' => $subtotal + $taxTotal,
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
                'total' => $subtotal + $taxTotal,
            ]);

            $this->recreatePayments($receipt, $data['payments'] ?? []);

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
            // 1. Post journal entry via SalesPostingService
            $context = $this->buildPostContext($receipt);

            $je = $this->postingService->postSale([
                'company_id' => $receipt->company_id,
                'user_id' => $userId,
                'source_module' => 'sales_receipt',
                'document_number' => $receipt->receipt_number,
                'date' => $receipt->receipt_date->format('Y-m-d'),
                'memo' => $receipt->memo ?? "Sales Receipt {$receipt->receipt_number}",
                'lines' => $context['lines'],
                'payments' => $context['payments'],
            ]);

            // 2. Consume inventory
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

            // 3. Update receipt
            $receipt->update([
                'status' => SalesReceipt::STATUS_POSTED,
                'journal_entry_id' => $je->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            $this->logReceiptAction($receipt, 'posted', null, $receipt->toArray(), $userId);

            DB::commit();
            return $receipt->fresh(['journalEntry', 'lines', 'payments']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

            // 2. Return inventory
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
}
