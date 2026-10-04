<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Customer;
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

    /**
     * Create a quotation (draft by default). Handles walk-in customer
     * auto-creation inside the same transaction (§R8) and persists the
     * sanctioned §7 column set.
     */
    public function create(array $data, int $userId): Quotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];
            $customer = $this->resolveCustomer($data, $companyId, $userId);

            $quotationNumber = $this->numberingService->getNextNumber($companyId, 'quotation');

            [$lines, $subtotal, $taxTotal] = $this->normalizeLines($data['lines']);

            $quotation = Quotation::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'customer_id' => $customer['id'],
                'customer_name' => $data['customer_name'] ?? $customer['name'],
                'contact_person' => $data['contact_person'] ?? $customer['contact_person'] ?? null,
                'email' => $data['email'] ?? $customer['email'] ?? null,
                'phone' => $data['phone'] ?? $customer['phone'] ?? null,
                'terms_key' => $data['terms_key'] ?? $customer['terms_key'] ?? null,
                'department' => $data['department'] ?? null,
                'prepared_by' => $data['prepared_by'] ?? null,
                'project' => $data['project'] ?? null,
                'quotation_number' => $quotationNumber,
                'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'reference' => $data['reference'] ?? null,
                'memo' => $data['memo'] ?? null,
                'notes_internal' => $data['notes_internal'] ?? null,
                'status' => $data['status'] ?? Quotation::STATUS_DRAFT,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => $userId,
            ]);

            $this->persistLines($quotation, $lines);

            $this->logAudit($quotation, 'created', null, [
                'number' => $quotationNumber,
                'status' => $quotation->status,
                'total' => $subtotal + $taxTotal,
            ]);

            if ($customer['created']) {
                $this->logAudit($quotation, 'customer_created', null, [
                    'customer_id' => $customer['id'],
                    'customer_name' => $customer['name'],
                ], 'Walk-in customer auto-created.');
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
            [$lines, $subtotal, $taxTotal] = $this->normalizeLines($data['lines']);

            $quotation->update([
                'branch_id' => $data['branch_id'] ?? $quotation->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $quotation->cost_center_id,
                'customer_id' => $data['customer_id'] ?? $quotation->customer_id,
                'customer_name' => $data['customer_name'] ?? $quotation->customer_name,
                'contact_person' => $data['contact_person'] ?? $quotation->contact_person,
                'email' => $data['email'] ?? $quotation->email,
                'phone' => $data['phone'] ?? $quotation->phone,
                'terms_key' => $data['terms_key'] ?? $quotation->terms_key,
                'department' => $data['department'] ?? $quotation->department,
                'prepared_by' => $data['prepared_by'] ?? $quotation->prepared_by,
                'project' => $data['project'] ?? $quotation->project,
                'quotation_date' => $data['quotation_date'] ?? $quotation->quotation_date,
                'valid_until' => $data['valid_until'] ?? $quotation->valid_until,
                'reference' => $data['reference'] ?? $quotation->reference,
                'memo' => $data['memo'] ?? $quotation->memo,
                'notes_internal' => $data['notes_internal'] ?? $quotation->notes_internal,
                'currency' => $data['currency'] ?? $quotation->currency,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);

            $quotation->lines()->delete();
            $this->persistLines($quotation, $lines);

            $this->logAudit($quotation, 'updated', null, [
                'number' => $quotation->quotation_number,
                'total' => $subtotal + $taxTotal,
            ]);

            return $quotation->fresh('lines');
        });
    }

    /**
     * Clone a source quotation into a brand-new quotation (§R7/§5). Sets the
     * source row's superseded_by link to the new record inside the same
     * transaction. $data overrides the carried fields.
     */
    public function requote(Quotation $source, array $data, int $userId): Quotation
    {
        return DB::transaction(function () use ($source, $data, $userId) {
            $source->loadMissing('lines');

            $carried = [
                'company_id' => $source->company_id,
                'branch_id' => $data['branch_id'] ?? $source->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $source->cost_center_id,
                'currency' => $data['currency'] ?? $source->currency,
                'department' => $data['department'] ?? $source->department,
                'prepared_by' => $data['prepared_by'] ?? $source->prepared_by,
                'project' => $data['project'] ?? $source->project,
                'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'reference' => $data['reference'] ?? $source->reference,
                'memo' => $data['memo'] ?? $source->memo,
                'notes_internal' => $data['notes_internal'] ?? $source->notes_internal,
                'status' => $data['status'] ?? Quotation::STATUS_DRAFT,
                'lines' => !empty($data['lines']) ? $data['lines'] : $this->carryLines($source),
            ];

            // Walk-in / edited customer on re-quote (§5.2 + R8).
            if (!empty($data['customer_name']) && !empty($data['is_walkin'])) {
                $carried['customer_id'] = null;
                $carried['customer_name'] = $data['customer_name'];
                $carried['contact_person'] = $data['contact_person'] ?? null;
                $carried['email'] = $data['email'] ?? null;
                $carried['phone'] = $data['phone'] ?? null;
                $carried['terms_key'] = $data['terms_key'] ?? $source->terms_key;
            } else {
                $carried['customer_id'] = $data['customer_id'] ?? $source->customer_id;
                $carried['customer_name'] = $data['customer_name'] ?? $source->customer_name ?? $source->customer?->name;
                $carried['contact_person'] = $data['contact_person'] ?? $source->contact_person;
                $carried['email'] = $data['email'] ?? $source->email;
                $carried['phone'] = $data['phone'] ?? $source->phone;
                $carried['terms_key'] = $data['terms_key'] ?? $source->terms_key;
            }

            $new = $this->create($carried, $userId);

            $source->update(['superseded_by' => $new->id]);

            $this->logAudit($source, 'superseded', ['superseded_by' => null], [
                'superseded_by' => $new->id,
                'superseded_number' => $new->quotation_number,
            ], "Re-quoted as {$new->quotation_number}.");

            return $new->fresh('lines');
        });
    }

    public function send(Quotation $quotation): Quotation
    {
        if ($quotation->status !== Quotation::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft quotations can be sent.');
        }
        $quotation->update(['status' => Quotation::STATUS_SENT]);
        $this->logAudit($quotation, 'sent', ['status' => Quotation::STATUS_DRAFT], ['status' => Quotation::STATUS_SENT]);

        return $quotation;
    }

    /**
     * Stamp the email dispatch (§6.2/§7.5). Never called on a failed send.
     */
    public function recordEmailed(Quotation $quotation, string $email): Quotation
    {
        $quotation->update(['emailed_at' => now(), 'emailed_to' => $email]);
        $this->logAudit($quotation, 'emailed', null, ['emailed_to' => $email]);

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
            $this->logAudit($quotation, 'deleted', ['number' => $quotation->quotation_number, 'status' => $quotation->status]);
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
        $this->logAudit($quotation, 'accepted', ['status' => Quotation::STATUS_SENT], ['status' => Quotation::STATUS_ACCEPTED]);

        return $quotation;
    }

    public function decline(Quotation $quotation): Quotation
    {
        if (!in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
            throw new InvalidArgumentException('Only sent or accepted quotations can be declined.');
        }
        $previous = $quotation->status;
        $quotation->update(['status' => Quotation::STATUS_DECLINED]);
        $this->logAudit($quotation, 'declined', ['status' => $previous], ['status' => Quotation::STATUS_DECLINED]);

        return $quotation;
    }

    public function convertToInvoice(Quotation $quotation, int $userId): \App\Models\Invoice
    {
        if (!in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
            throw new InvalidArgumentException('Only sent or accepted quotations can be converted.');
        }

        $previous = $quotation->status;
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

        $this->logAudit($quotation, 'converted', ['status' => $previous], [
            'status' => Quotation::STATUS_CONVERTED,
            'invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    public function convertToSalesReceipt(Quotation $quotation, array $paymentData, int $userId): \App\Models\SalesReceipt
    {
        if (!in_array($quotation->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
            throw new InvalidArgumentException('Only sent or accepted quotations can be converted.');
        }

        $previous = $quotation->status;
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

        $this->logAudit($quotation, 'converted', ['status' => $previous], [
            'status' => Quotation::STATUS_CONVERTED,
            'receipt_id' => $receipt->id,
        ]);

        return $receipt;
    }

    public function void(Quotation $quotation, string $reason, int $userId): Quotation
    {
        if ($quotation->status === Quotation::STATUS_VOID) {
            throw new InvalidArgumentException('Quotation is already void.');
        }
        $previous = $quotation->status;
        $quotation->update([
            'status' => Quotation::STATUS_VOID,
            'voided_by' => $userId,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);

        $this->logAudit($quotation, 'voided', ['status' => $previous], [
            'status' => Quotation::STATUS_VOID,
            'void_reason' => $reason,
        ]);

        return $quotation;
    }

    /**
     * Resolve the customer for a quotation payload. Accepts an existing
     * customer_id or a walk-in name (auto-created, §R8). Returns the customer
     * snapshot used on the quotation header.
     */
    private function resolveCustomer(array $data, int $companyId, int $userId): array
    {
        if (!empty($data['customer_id'])) {
            $customer = Customer::where('company_id', $companyId)->find($data['customer_id']);
            if (!$customer) {
                throw new InvalidArgumentException('Customer not found.');
            }

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'contact_person' => $data['contact_person'] ?? $customer->display_name ?? $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'terms_key' => $customer->payment_terms,
                'created' => false,
            ];
        }

        $name = trim((string) ($data['customer_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('A customer is required.');
        }

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => $name,
            'display_name' => $data['contact_person'] ?? $name,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'currency' => $data['currency'] ?: 'USD',
            'payment_terms' => ($data['terms_key'] ?? null) ?: 'net_30',
            'is_active' => true,
        ]);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'contact_person' => $data['contact_person'] ?? $name,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'terms_key' => $data['terms_key'] ?? null,
            'created' => true,
        ];
    }

    /**
     * Normalise raw line payloads into the persisted shape. §7.4 money:
     * line net = qty × unit × (1 − disc%); tax = net × rate/100.
     *
     * @return array{0: array<int,array>, 1: float, 2: float}
     */
    private function normalizeLines(array $lines): array
    {
        $normalised = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $position = 0;

        foreach ($lines as $line) {
            $position++;
            $qty = (float) ($line['quantity'] ?? $line['qty'] ?? 0);
            $unit = (float) ($line['unit_price'] ?? 0);
            $gross = round($qty * $unit, 2);

            $discPct = (float) ($line['disc_pct'] ?? 0);
            if ($discPct <= 0 && isset($line['discount']) && (float) $line['discount'] > 0 && $gross > 0) {
                // Legacy callers pass an absolute discount amount.
                $discPct = round(((float) $line['discount']) / $gross * 100, 3);
            }
            $discAbs = $discPct > 0 ? round($gross * $discPct / 100, 2) : 0.0;
            $net = round($gross - $discAbs, 2);
            $taxRate = (float) ($line['tax_rate'] ?? 0);
            $tax = round($net * $taxRate / 100, 2);

            $subtotal += $net;
            $taxTotal += $tax;

            $normalised[] = [
                'position' => $position,
                'product_id' => $line['product_id'] ?? null,
                'item_code' => $line['item_code'] ?? null,
                'item_name' => $line['item_name'] ?? ($line['description'] ?? null),
                'description' => $line['description'] ?? ($line['item_name'] ?? ''),
                'quantity' => $qty,
                'unit_price' => $unit,
                'disc_pct' => $discPct,
                'discount' => $discAbs,
                'tax_rate' => $taxRate,
                'amount' => $net,
                'tax_amount' => $tax,
                'line_total' => $net + $tax,
                'income_account_id' => $line['income_account_id'] ?? null,
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ];
        }

        return [$normalised, round($subtotal, 2), round($taxTotal, 2)];
    }

    private function persistLines(Quotation $quotation, array $lines): void
    {
        foreach ($lines as $line) {
            $line['quotation_id'] = $quotation->id;
            QuotationLine::create($line);
        }
    }

    /**
     * Carry source lines into a re-quote payload, preserving discount % and
     * unit prices.
     */
    private function carryLines(Quotation $source): array
    {
        return $source->lines->map(fn($l) => [
            'product_id' => $l->product_id,
            'item_code' => $l->item_code,
            'item_name' => $l->item_name,
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => $l->unit_price,
            'disc_pct' => $l->disc_pct,
            'discount' => $l->discount,
            'tax_rate' => $l->tax_rate,
            'income_account_id' => $l->income_account_id,
            'cost_center_id' => $l->cost_center_id,
        ])->toArray();
    }

    private function logAudit(Quotation $quotation, string $action, ?array $old = null, ?array $new = null, ?string $notes = null): void
    {
        AuditLog::log(
            $quotation->company_id,
            auth()->id() ?? $quotation->created_by,
            Quotation::class,
            $quotation->id,
            $action,
            $old,
            $new,
            $notes
        );
    }
}
