<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuotationService
{
    public function __construct(
        protected JournalPostingEngine $postingEngine,
        protected NumberingSequenceService $numberingService,
    ) {}

    public function create(array $data, int $userId): Quotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];

            $quotationNumber = $this->numberingService->getNextNumber(
                $companyId,
                'quotation'
            );

            $subtotal = 0;
            $taxTotal = 0;
            foreach ($data['lines'] as $line) {
                $amount = round($line['quantity'] * $line['unit_price'], 2);
                $discount = round($line['discount'] ?? 0, 2);
                $net = $amount - $discount;
                $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);
                $subtotal += $net;
                $taxTotal += $tax;
            }

            $quotation = Quotation::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'quotation_number' => $quotationNumber,
                'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'status' => Quotation::STATUS_DRAFT,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $line) {
                $amount = round($line['quantity'] * $line['unit_price'], 2);
                $discount = round($line['discount'] ?? 0, 2);
                $net = $amount - $discount;
                $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);

                QuotationLine::create([
                    'quotation_id' => $quotation->id,
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

            return $quotation->fresh('lines');
        });
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        if (!$quotation->isDraft()) {
            throw new InvalidArgumentException('Only draft quotations can be updated.');
        }

        return DB::transaction(function () use ($quotation, $data) {
            $subtotal = 0;
            $taxTotal = 0;
            foreach ($data['lines'] as $line) {
                $amount = round($line['quantity'] * $line['unit_price'], 2);
                $discount = round($line['discount'] ?? 0, 2);
                $net = $amount - $discount;
                $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);
                $subtotal += $net;
                $taxTotal += $tax;
            }

            $quotation->update([
                'branch_id' => $data['branch_id'] ?? $quotation->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $quotation->cost_center_id,
                'customer_id' => $data['customer_id'] ?? $quotation->customer_id,
                'quotation_date' => $data['quotation_date'] ?? $quotation->quotation_date,
                'valid_until' => $data['valid_until'] ?? $quotation->valid_until,
                'reference' => $data['reference'] ?? $quotation->reference,
                'memo' => $data['memo'] ?? $quotation->memo,
                'currency' => $data['currency'] ?? $quotation->currency,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);

            $quotation->lines()->delete();
            foreach ($data['lines'] as $line) {
                $amount = round($line['quantity'] * $line['unit_price'], 2);
                $discount = round($line['discount'] ?? 0, 2);
                $net = $amount - $discount;
                $tax = round($net * ($line['tax_rate'] ?? 0) / 100, 2);

                QuotationLine::create([
                    'quotation_id' => $quotation->id,
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

            return $quotation->fresh('lines');
        });
    }

    public function send(Quotation $quotation): Quotation
    {
        if ($quotation->status !== Quotation::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft quotations can be sent.');
        }
        $quotation->update(['status' => Quotation::STATUS_SENT]);
        return $quotation;
    }

    /**
     * Delete a draft quotation and its line items.
     * Any non-draft status is rejected so a quoted/approved document can never
     * be removed after the fact.
     */
    public function destroy(Quotation $quotation): void
    {
        if ($quotation->status !== Quotation::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft quotations can be deleted.');
        }

        DB::transaction(function () use ($quotation) {
            $quotation->lines()->delete();
            $quotation->delete();
        });
    }

    public function accept(Quotation $quotation): Quotation
    {
        if ($quotation->status !== Quotation::STATUS_SENT) {
            throw new InvalidArgumentException('Only sent quotations can be accepted.');
        }
        $quotation->update(['status' => Quotation::STATUS_ACCEPTED]);
        return $quotation;
    }

    public function decline(Quotation $quotation): Quotation
    {
        if (!in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
            throw new InvalidArgumentException('Only sent or accepted quotations can be declined.');
        }
        $quotation->update(['status' => Quotation::STATUS_DECLINED]);
        return $quotation;
    }

    public function convertToInvoice(Quotation $quotation, int $userId): \App\Models\Invoice
    {
        if (!in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
            throw new InvalidArgumentException('Only sent or accepted quotations can be converted.');
        }

        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->create([
            'company_id' => $quotation->company_id,
            'branch_id' => $quotation->branch_id,
            'cost_center_id' => $quotation->cost_center_id,
            'customer_id' => $quotation->customer_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'reference' => $quotation->reference,
            'memo' => "Converted from Quotation {$quotation->quotation_number}",
            'currency' => $quotation->currency,
            'lines' => $quotation->lines->map(fn($l) => [
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

        $quotation->update([
            'status' => Quotation::STATUS_CONVERTED,
            'converted_invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    public function convertToSalesReceipt(Quotation $quotation, array $paymentData, int $userId): \App\Models\SalesReceipt
    {
        if (!in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
            throw new InvalidArgumentException('Only sent or accepted quotations can be converted.');
        }

        $receiptService = app(SalesReceiptService::class);
        $receipt = $receiptService->create([
            'company_id' => $quotation->company_id,
            'branch_id' => $quotation->branch_id,
            'cost_center_id' => $quotation->cost_center_id,
            'customer_id' => $quotation->customer_id,
            'receipt_date' => now()->toDateString(),
            'reference' => $quotation->reference,
            'memo' => "Converted from Quotation {$quotation->quotation_number}",
            'currency' => $quotation->currency,
            'lines' => $quotation->lines->map(fn($l) => [
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

        $quotation->update([
            'status' => Quotation::STATUS_CONVERTED,
            'converted_receipt_id' => $receipt->id,
        ]);

        return $receipt;
    }

    public function void(Quotation $quotation, string $reason, int $userId): Quotation
    {
        if ($quotation->status === Quotation::STATUS_VOID) {
            throw new InvalidArgumentException('Quotation is already void.');
        }
        $quotation->update([
            'status' => Quotation::STATUS_VOID,
            'voided_by' => $userId,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
        return $quotation;
    }
}
