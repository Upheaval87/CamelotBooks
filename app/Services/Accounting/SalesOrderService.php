<?php

namespace App\Services\Accounting;

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesReceipt;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderService
{
    public function __construct(
        protected NumberingSequenceService $numberingService,
    ) {}

    public function create(array $data, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];

            $orderNumber = $this->numberingService->getNextNumber(
                $companyId,
                'sales_order'
            );

            [$subtotal, $taxTotal] = $this->totals($data['lines']);

            $order = SalesOrder::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'sales_order_number' => $orderNumber,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => SalesOrder::STATUS_DRAFT,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            $this->syncLines($order, $data['lines']);

            return $order->fresh('lines');
        });
    }

    public function update(SalesOrder $order, array $data): SalesOrder
    {
        if (!$order->isDraft()) {
            throw new InvalidArgumentException('Only draft sales orders can be updated.');
        }

        return DB::transaction(function () use ($order, $data) {
            [$subtotal, $taxTotal] = $this->totals($data['lines']);

            $order->update([
                'branch_id' => $data['branch_id'] ?? $order->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $order->cost_center_id,
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $order->expected_delivery_date,
                'reference' => $data['reference'] ?? $order->reference,
                'memo' => $data['memo'] ?? $order->memo,
                'currency' => $data['currency'] ?? $order->currency,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);

            $this->syncLines($order, $data['lines']);

            return $order->fresh('lines');
        });
    }

    public function send(SalesOrder $order): SalesOrder
    {
        $this->assertStatus($order, SalesOrder::STATUS_DRAFT, 'send');
        $order->update(['status' => SalesOrder::STATUS_SENT]);
        return $order;
    }

    public function confirm(SalesOrder $order): SalesOrder
    {
        $this->assertStatus($order, SalesOrder::STATUS_SENT, 'confirm');
        $order->update(['status' => SalesOrder::STATUS_CONFIRMED]);
        return $order;
    }

    public function markFulfilled(SalesOrder $order): SalesOrder
    {
        if (!in_array($order->status, [SalesOrder::STATUS_SENT, SalesOrder::STATUS_CONFIRMED])) {
            throw new InvalidArgumentException('Only sent or confirmed sales orders can be marked as fulfilled.');
        }
        $order->update(['status' => SalesOrder::STATUS_FULFILLED]);
        return $order;
    }

    public function cancel(SalesOrder $order): SalesOrder
    {
        if (!in_array($order->status, [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_SENT, SalesOrder::STATUS_CONFIRMED])) {
            throw new InvalidArgumentException('Only draft, sent or confirmed sales orders can be cancelled.');
        }
        $order->update(['status' => SalesOrder::STATUS_CANCELLED]);
        return $order;
    }

    public function destroy(SalesOrder $order): void
    {
        $this->assertStatus($order, SalesOrder::STATUS_DRAFT, 'delete');

        DB::transaction(function () use ($order) {
            $order->lines()->delete();
            $order->delete();
        });
    }

    public function convertToInvoice(SalesOrder $order, int $userId): Invoice
    {
        $this->assertConvertible($order);

        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->create([
            'company_id' => $order->company_id,
            'branch_id' => $order->branch_id,
            'cost_center_id' => $order->cost_center_id,
            'customer_id' => $order->customer_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'reference' => $order->reference,
            'memo' => "Converted from Sales Order {$order->sales_order_number}",
            'currency' => $order->currency,
            'lines' => $order->lines->map(fn($l) => [
                'product_id' => $l->product_id,
                'description' => $l->description,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'discount' => $l->discount,
                'tax_rate' => $l->tax_rate,
                'income_account_id' => $l->income_account_id,
                'cost_center_id' => $l->cost_center_id,
            ])->toArray(),
        ], $userId);

        $order->update([
            'status' => SalesOrder::STATUS_FULFILLED,
            'converted_invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    public function convertToSalesReceipt(SalesOrder $order, array $paymentData, int $userId): SalesReceipt
    {
        $this->assertConvertible($order);

        $receiptService = app(SalesReceiptService::class);
        $receipt = $receiptService->create([
            'company_id' => $order->company_id,
            'branch_id' => $order->branch_id,
            'cost_center_id' => $order->cost_center_id,
            'customer_id' => $order->customer_id,
            'receipt_date' => now()->toDateString(),
            'reference' => $order->reference,
            'memo' => "Converted from Sales Order {$order->sales_order_number}",
            'currency' => $order->currency,
            'lines' => $order->lines->map(fn($l) => [
                'product_id' => $l->product_id,
                'description' => $l->description,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'discount' => $l->discount,
                'tax_rate' => $l->tax_rate,
                'income_account_id' => $l->income_account_id,
                'cost_center_id' => $l->cost_center_id,
            ])->toArray(),
            'payments' => $paymentData['payments'] ?? [],
        ], $userId);

        $order->update([
            'status' => SalesOrder::STATUS_FULFILLED,
            'converted_receipt_id' => $receipt->id,
        ]);

        return $receipt;
    }

    public function void(SalesOrder $order, string $reason, int $userId): SalesOrder
    {
        if ($order->status === SalesOrder::STATUS_VOID) {
            throw new InvalidArgumentException('Sales order is already void.');
        }
        $order->update([
            'status' => SalesOrder::STATUS_VOID,
            'voided_by' => $userId,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
        return $order;
    }

    protected function totals(array $lines): array
    {
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($lines as $line) {
            $amount = round($line['quantity'] * $line['unit_price'], 2);
            $discount = round($line['discount'] ?? 0, 2);
            $net = $amount - $discount;
            $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);
            $subtotal += $net;
            $taxTotal += $tax;
        }
        return [$subtotal, $taxTotal];
    }

    protected function syncLines(SalesOrder $order, array $lines): void
    {
        $order->lines()->delete();
        foreach ($lines as $line) {
            $amount = round($line['quantity'] * $line['unit_price'], 2);
            $discount = round($line['discount'] ?? 0, 2);
            $net = $amount - $discount;
            $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);

            SalesOrderLine::create([
                'sales_order_id' => $order->id,
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
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ]);
        }
    }

    protected function assertStatus(SalesOrder $order, string $status, string $action): void
    {
        if ($order->status !== $status) {
            throw new InvalidArgumentException("Only {$status} sales orders can be {$action}d.");
        }
    }

    protected function assertConvertible(SalesOrder $order): void
    {
        if (!in_array($order->status, [
            SalesOrder::STATUS_SENT,
            SalesOrder::STATUS_CONFIRMED,
            SalesOrder::STATUS_FULFILLED,
        ])) {
            throw new InvalidArgumentException('Only sent, confirmed or fulfilled sales orders can be converted.');
        }
    }
}
