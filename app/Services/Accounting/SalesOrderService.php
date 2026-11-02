<?php

namespace App\Services\Accounting;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OrderFulfilment;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesReceipt;
use App\Services\Admin\NumberingSequenceService;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * SALES ORDERS MODULE — §5 sanctioned backend contract.
 *
 * Every lifecycle transition, fulfilment batch, email dispatch and invoice
 * creation is written to the audit trail with the acting user, a timestamp and
 * the from→to pair (§5.5). Cancel and void RETAIN the rows: there is no hard
 * delete anywhere in the status machine.
 */
class SalesOrderService
{
    /**
     * R14 · Mirrored server-side. The browser checks the same expression before
     * it lets the button submit, but this is the authority.
     */
    public const EMAIL_PATTERN = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/';

    public const MODE_DRAFT = 'draft';

    public const MODE_RELEASE = 'release';

    public function __construct(
        protected NumberingSequenceService $numberingService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────
    // R9 / R10 · write path
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param  array  $data  request payload, already validated
     * @param  string $mode  draft|release
     */
    public function create(array $data, int $userId, string $mode = self::MODE_DRAFT): SalesOrder
    {
        if ($mode === self::MODE_RELEASE) {
            $this->assertNoReleaseGaps($data);
        }

        return DB::transaction(function () use ($data, $userId, $mode) {
            $companyId = (int) $data['company_id'];

            // R10 · a walk-in name becomes a real customer row inside THIS
            // transaction, so the order always resolves to a customer record.
            $customer = $this->resolveCustomer($companyId, $data);

            $orderNumber = $this->numberingService->getNextNumber($companyId, 'sales_order');

            $lines = $this->normaliseLines($data['lines'] ?? []);
            [$subtotal, $taxTotal] = $this->totals($lines);

            $order = SalesOrder::create($this->orderAttributes($data, [
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'sales_order_number' => $orderNumber,
                'status' => SalesOrder::STATUS_DRAFT,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'created_by' => $userId,
            ], $customer));

            $this->syncLines($order, $lines);

            AuditLog::log(
                $companyId,
                $userId,
                SalesOrder::class,
                $order->id,
                'created',
                null,
                ['status' => SalesOrder::STATUS_DRAFT, 'total' => (float) $order->total],
                "Sales Order {$order->sales_order_number} created as draft."
            );

            if ($mode === self::MODE_RELEASE) {
                // R14 · release auto-emails when a valid address already exists.
                $this->release($order, $userId, ['auto_email' => true]);
            }

            return $order->fresh(['lines']);
        });
    }

    /**
     * §5.2 · PUT /sales-orders/{id} — 409 unless draft (R8).
     */
    public function update(SalesOrder $order, array $data, int $userId, string $mode = self::MODE_DRAFT): SalesOrder
    {
        $this->assertDraft($order, 'updated');

        if ($mode === self::MODE_RELEASE) {
            $this->assertNoReleaseGaps($data);
        }

        return DB::transaction(function () use ($order, $data, $userId, $mode) {
            $companyId = (int) $order->company_id;
            $customer = $this->resolveCustomer($companyId, $data);

            $lines = $this->normaliseLines($data['lines'] ?? []);
            [$subtotal, $taxTotal] = $this->totals($lines);

            $order->update($this->orderAttributes($data, [
                'customer_id' => $customer->id,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ], $customer));

            $this->syncLines($order, $lines);

            AuditLog::log(
                $companyId,
                $userId,
                SalesOrder::class,
                $order->id,
                'updated',
                ['total' => null],
                ['total' => (float) $order->total],
                "Sales Order {$order->sales_order_number} draft updated."
            );

            if ($mode === self::MODE_RELEASE) {
                $this->release($order, $userId, ['auto_email' => true]);
            }

            return $order->fresh(['lines']);
        });
    }

    /**
     * Duplicate a closed order back in as a brand-new draft (§2.2 row action,
     * view-modal footer, §8.2 "cancel/void ⇒ retained + duplicate").
     */
    public function duplicate(SalesOrder $order, int $userId): SalesOrder
    {
        $data = [
            'company_id' => $order->company_id,
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer_name,
            'contact_person' => $order->contact_person,
            'email' => $order->email,
            'phone' => $order->phone,
            'delivery_address' => $order->delivery_address,
            'branch_id' => $order->branch_id,
            'cost_center_id' => $order->cost_center_id,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => $order->expected_delivery_date,
            'reference' => $order->reference,
            'memo' => $order->memo,
            'notes_internal' => $order->notes_internal,
            'delivery_method' => $order->delivery_method,
            'department' => $order->department,
            'prepared_by' => $order->prepared_by,
            'terms_key' => $order->terms_key,
            'currency' => $order->currency,
            'lines' => $order->lines->map(fn (SalesOrderLine $l) => [
                'product_id' => $l->product_id,
                'item_code' => $l->item_code,
                'item_name' => $l->item_name,
                'description' => $l->description,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'discount' => $l->discount,
                'tax_rate' => $l->tax_rate,
                'income_account_id' => $l->income_account_id,
                'cost_center_id' => $l->cost_center_id,
            ])->toArray(),
        ];

        return $this->create($data, $userId, self::MODE_DRAFT);
    }

    // ─────────────────────────────────────────────────────────────────────
    // R8 · status machine
    // ─────────────────────────────────────────────────────────────────────

    /** draft→sent. */
    public function release(SalesOrder $order, int $userId, array $options = []): SalesOrder
    {
        $this->assertDraft($order, 'released');

        // R14 · release with an email requires a well-formed address; a bad one
        // blocks the release rather than silently shipping nowhere.
        $email = trim((string) ($options['email'] ?? $order->email));
        if (filled($email) && ! preg_match(self::EMAIL_PATTERN, $email)) {
            throw new InvalidArgumentException("\"{$email}\" is not a valid email address.");
        }

        return DB::transaction(function () use ($order, $userId, $options, $email) {
            $from = $order->status;

            $order->forceFill([
                'status' => SalesOrder::STATUS_SENT,
                'sent_at' => now(),
            ])->save();

            AuditLog::log(
                (int) $order->company_id,
                $userId,
                SalesOrder::class,
                $order->id,
                'released',
                ['status' => $from],
                ['status' => SalesOrder::STATUS_SENT],
                "Sales Order {$order->sales_order_number} released to the customer."
            );

            // R14 · release auto-emails when a valid address is on file.
            if (($options['auto_email'] ?? false) && filled($email)) {
                $this->recordEmailed($order, $email, $userId, 'Released with auto-email.');
            }

            return $order;
        });
    }

    /** sent→confirmed, with stock allocation for every line (§5.1). */
    public function confirm(SalesOrder $order, int $userId): SalesOrder
    {
        $this->assertStatus($order, SalesOrder::STATUS_SENT, 'confirmed');

        return DB::transaction(function () use ($order, $userId) {
            $order->loadMissing('lines.product');

            $from = $order->status;

            $order->forceFill([
                'status' => SalesOrder::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ])->save();

            // §5.1 · reservation on confirm.
            foreach ($order->lines as $line) {
                if (! $line->product_id) {
                    continue;
                }

                $this->movement(
                    (int) $order->company_id,
                    $order,
                    $line,
                    (float) $line->quantity,
                    'allocation',
                    "Allocation on confirmation of {$order->sales_order_number}"
                );
            }

            AuditLog::log(
                (int) $order->company_id,
                $userId,
                SalesOrder::class,
                $order->id,
                'confirmed',
                ['status' => $from],
                ['status' => SalesOrder::STATUS_CONFIRMED],
                "Sales Order {$order->sales_order_number} confirmed; stock allocated."
            );

            return $order;
        });
    }

    /** draft|sent|confirmed→cancelled. Rows are retained (§5.5). */
    public function cancel(SalesOrder $order, ?string $reason, int $userId): SalesOrder
    {
        if (! in_array($order->status, [
            SalesOrder::STATUS_DRAFT,
            SalesOrder::STATUS_SENT,
            SalesOrder::STATUS_CONFIRMED,
        ], true)) {
            throw new InvalidArgumentException('Only draft, sent or confirmed sales orders can be cancelled.');
        }

        $from = $order->status;

        $order->forceFill([
            'status' => SalesOrder::STATUS_CANCELLED,
            'cancel_reason' => $reason,
        ])->save();

        $this->audit((int) $order->company_id, $order, $userId, 'cancelled', $from, SalesOrder::STATUS_CANCELLED, $reason);

        return $order;
    }

    /** draft|sent→void. */
    public function void(SalesOrder $order, string $reason, int $userId): SalesOrder
    {
        if (! in_array($order->status, [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_SENT], true)) {
            throw new InvalidArgumentException('Only draft or sent sales orders can be voided.');
        }

        $from = $order->status;

        $order->forceFill([
            'status' => SalesOrder::STATUS_VOID,
            'voided_by' => $userId,
            'voided_at' => now(),
            'void_reason' => $reason,
        ])->save();

        $this->audit((int) $order->company_id, $order, $userId, 'voided', $from, SalesOrder::STATUS_VOID, $reason);

        return $order;
    }

    // ─────────────────────────────────────────────────────────────────────
    // R12 · fulfilment
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Record a delivery batch. Partial batches keep the order confirmed and set
     * the amber Partial chip; a batch covering every remaining line flips the
     * order to fulfilled and stores delivered_on / delivery note / received_by.
     *
     * @param  array  $data  {date, dn_no?, received_by, lines:[{line_id, qty}]}
     */
    public function recordFulfilment(SalesOrder $order, array $data, int $userId): array
    {
        // §5.2 · fulfilment is gated on `confirmed`: releasing an order does not
        // move goods, so only a confirmed (allocated) order can be delivered
        // against.
        $this->assertStatus($order, SalesOrder::STATUS_CONFIRMED, 'fulfilled');

        $order->loadMissing('lines');

        $date = $data['date'] ?? now()->toDateString();
        $receivedBy = trim((string) ($data['received_by'] ?? '')) ?: null;

        $requested = collect($data['lines'] ?? [])
            ->map(fn ($row) => [
                'line_id' => (int) ($row['line_id'] ?? 0),
                'qty' => round((float) ($row['qty'] ?? 0), 2),
            ])
            ->filter(fn ($row) => $row['line_id'] > 0 && $row['qty'] > 0)
            ->keyBy('line_id');

        if ($requested->isEmpty()) {
            throw new InvalidArgumentException('Enter a fulfilment quantity on at least one line.');
        }

        return DB::transaction(function () use ($order, $requested, $date, $receivedBy, $data, $userId) {
            $companyId = (int) $order->company_id;

            // §5.3 · one DN number per fulfilment batch.
            $dn = trim((string) ($data['dn_no'] ?? ''))
                ?: $this->numberingService->getNextNumber($companyId, 'delivery_note');

            $batchQty = 0.0;

            foreach ($requested as $lineId => $row) {
                $line = $order->lines->firstWhere('id', $lineId);

                if (! $line) {
                    throw new InvalidArgumentException("Line {$lineId} does not belong to this sales order.");
                }

                $remaining = $line->remaining();

                // §5.4 · the cap is enforced server-side, not just in the UI.
                if ($row['qty'] > $remaining) {
                    throw new InvalidArgumentException(
                        "Only {$remaining} remaining on \"{$line->label()}\"; you entered {$row['qty']}."
                    );
                }

                $line->forceFill([
                    'qty_fulfilled' => round((float) $line->qty_fulfilled + $row['qty'], 2),
                ])->save();

                OrderFulfilment::create([
                    'company_id' => $companyId,
                    'sales_order_id' => $order->id,
                    'sales_order_line_id' => $line->id,
                    'quantity' => $row['qty'],
                    'fulfil_date' => $date,
                    'delivery_note_no' => $dn,
                    'received_by' => $receivedBy,
                    'created_by' => $userId,
                ]);

                // §5.1 · relief on fulfilment, from the order warehouse.
                if ($line->product_id) {
                    $this->movement(
                        $companyId,
                        $order,
                        $line,
                        -1 * $row['qty'],
                        'relief',
                        "Delivery {$dn} from {$order->sales_order_number}"
                    );
                }

                $batchQty += $row['qty'];
            }

            $order->refresh('lines');
            $progress = $order->fulfilmentProgress();

            if ($progress['complete']) {
                $order->forceFill([
                    'status' => SalesOrder::STATUS_FULFILLED,
                    'fulfilled_at' => now(),
                    'delivered_on' => $date,
                    'delivery_note_no' => $dn,
                    'received_by' => $receivedBy,
                ])->save();
            }

            $this->audit(
                $companyId,
                $order,
                $userId,
                'fulfilled',
                SalesOrder::STATUS_CONFIRMED,
                (string) $order->status,
                "Delivery note {$dn} — {$batchQty} qty of {$progress['qty_ordered']}."
            );

            return [
                'order' => $order->fresh(['lines']),
                'progress' => $progress,
                'delivery_note_no' => $dn,
                'batch_qty' => round($batchQty, 2),
                'complete' => $progress['complete'],
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // R14 / R15 · email + invoice
    // ─────────────────────────────────────────────────────────────────────

    /** Stamp + audit an acknowledgement dispatch. The mail itself is queued by the caller. */
    public function recordEmailed(SalesOrder $order, string $recipient, int $userId, ?string $notes = null): SalesOrder
    {
        $recipient = trim($recipient);

        if (! preg_match(self::EMAIL_PATTERN, $recipient)) {
            throw new InvalidArgumentException('Enter a valid email address before sending.');
        }

        $previous = array_filter([
            'emailed_to' => $order->emailed_to,
            'emailed_at' => $order->emailed_at?->toDateTimeString(),
        ]);

        $order->forceFill([
            'emailed_to' => $recipient,
            'emailed_at' => now(),
        ])->saveQuietly();

        AuditLog::log(
            (int) $order->company_id,
            $userId,
            SalesOrder::class,
            $order->id,
            'emailed',
            $previous ?: null,
            ['emailed_to' => $recipient, 'emailed_at' => now()->toDateTimeString()],
            $notes ?: "Acknowledgement dispatched to {$recipient}."
        );

        return $order;
    }

    /** R15 · raise the invoice for the order grand total and cross-link both ways. */
    public function createInvoice(SalesOrder $order, int $userId): Invoice
    {
        if ($order->status !== SalesOrder::STATUS_FULFILLED) {
            throw new InvalidArgumentException('Only fulfilled sales orders can be invoiced.');
        }

        if ($order->converted_invoice_id && $invoice = $order->convertedInvoice) {
            return $invoice;
        }

        $order->loadMissing('lines');

        return DB::transaction(function () use ($order, $userId) {
            $invoice = app(InvoiceService::class)->create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'cost_center_id' => $order->cost_center_id,
                'customer_id' => $order->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($this->paymentTermDaysFor($order))->toDateString(),
                'reference' => $order->reference,
                'memo' => "Converted from Sales Order {$order->sales_order_number}",
                'currency' => $order->currency,
                'lines' => $order->lines->map(fn (SalesOrderLine $l) => [
                    'product_id' => $l->product_id,
                    'description' => $l->label(),
                    'quantity' => $l->quantity,
                    'unit_price' => $l->unit_price,
                    'discount' => $l->discount,
                    'tax_rate' => $l->tax_rate,
                    'income_account_id' => $l->income_account_id,
                    'cost_center_id' => $l->cost_center_id,
                ])->toArray(),
            ], $userId);

            // Cross-link both ways (§5.1 / R15).
            $order->forceFill(['converted_invoice_id' => $invoice->id])->save();

            $this->audit(
                (int) $order->company_id,
                $order,
                $userId,
                'invoiced',
                null,
                "Invoice {$invoice->invoice_number}",
                "Invoice {$invoice->invoice_number} raised from {$order->sales_order_number}."
            );

            return $invoice;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // R9 · gating
    // ─────────────────────────────────────────────────────────────────────

    /**
     * The single source of truth for what is still missing before an order can
     * be released. The summary-rail check row, the Release button's title and
     * the server-side guard all read this.
     *
     * @return array<int,string> unmet conditions, in the order they should be shown
     */
    public function releaseGaps(array $data): array
    {
        $gaps = [];

        $isWalkIn = $this->isWalkInCustomer($data);

        if ($isWalkIn) {
            if (! trim((string) ($data['customer_name'] ?? ''))) {
                $gaps[] = 'Customer name';
            }
        } elseif (! ($data['customer_id'] ?? null)) {
            $gaps[] = 'Customer';
        }

        // R9 requires contact person + delivery date in BOTH modes; on an
        // existing-customer match these arrive pre-filled and read-only.
        if (! trim((string) ($data['contact_person'] ?? ''))) {
            $gaps[] = 'Contact person';
        }

        if (! trim((string) ($data['expected_delivery_date'] ?? ''))) {
            $gaps[] = 'Delivery date';
        }

        $complete = $this->completeLineCount($data['lines'] ?? []);

        if ($complete < 1) {
            $gaps[] = '1 complete line';
        }

        return $gaps;
    }

    /**
     * R10 · walk-in detection. The create page sends `customer_type` as
     * `walkin` (per APPENDIX A) while the older contract used `walk_in`, so
     * both are accepted. When the flag is absent we still infer walk-in from
     * "no registry id but a typed name", which is how the form behaves.
     */
    public function isWalkInCustomer(array $data): bool
    {
        $mode = strtolower(trim((string) ($data['customer_type'] ?? '')));

        if (in_array($mode, ['walk_in', 'walkin'], true)) {
            return true;
        }

        return ! ($data['customer_id'] ?? null)
            && trim((string) ($data['customer_name'] ?? '')) !== '';
    }

    /**
     * Save Draft needs a customer and ≥1 complete line; Release additionally
     * needs contact person and delivery date.
     *
     * @return array<int,string>
     */
    public function draftGaps(array $data): array
    {
        $gaps = $this->releaseGaps($data);

        // Drafts do not need the delivery detail that release demands.
        return array_values(array_filter($gaps, fn ($gap) => $gap !== 'Delivery date' && $gap !== 'Contact person'));
    }

    public function assertNoReleaseGaps(array $data): void
    {
        $gaps = $this->releaseGaps($data);

        if ($gaps) {
            throw new InvalidArgumentException('Missing: ' . implode(', ', $gaps) . '.');
        }
    }

    /**
     * R9 · a line is complete with code + name + qty + price + amount; the
     * description is explicitly OPTIONAL.
     */
    public function completeLineCount(array $lines): int
    {
        $count = 0;

        foreach ($lines as $line) {
            if ($this->isLineComplete($line)) {
                $count++;
            }
        }

        return $count;
    }

    public function isLineComplete(array $line): bool
    {
        return trim((string) ($line['item_code'] ?? '')) !== ''
            && trim((string) ($line['item_name'] ?? '')) !== ''
            && (float) ($line['quantity'] ?? 0) > 0
            && (float) ($line['unit_price'] ?? 0) > 0
            && $this->lineAmount($line) > 0;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────

    /**
     * R10 · resolve the customer: a registry match is used as-is, a walk-in name
     * is created here and linked. An existing match inherits the registry's
     * contact details so the UI can lock them.
     */
    protected function resolveCustomer(int $companyId, array $data): Customer
    {
        $isWalkIn = $this->isWalkInCustomer($data);
        $customerId = $data['customer_id'] ?? null;

        if ($isWalkIn) {
            $name = trim((string) ($data['customer_name'] ?? ''));

            if ($name === '') {
                throw new InvalidArgumentException('Enter a customer name for the walk-in order.');
            }

            $customer = Customer::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'name' => $name,
                'display_name' => trim((string) ($data['contact_person'] ?? '')) ?: null,
                'email' => trim((string) ($data['email'] ?? '')) ?: null,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'billing_address' => trim((string) ($data['delivery_address'] ?? '')) ?: null,
                'is_active' => true,
            ]);

            $ref = (string) ($data['sales_order_number'] ?? '');

            AuditLog::log(
                $companyId,
                $data['created_by'] ?? null,
                Customer::class,
                $customer->id,
                'created',
                null,
                ['name' => $customer->name],
                'Walk-in customer captured on sales order ' . $ref . '.'
            );

            return $customer;
        }

        if (! $customerId) {
            throw new InvalidArgumentException('Select a customer from the registry.');
        }

        $customer = Customer::find($customerId);

        if (! $customer || (int) $customer->company_id !== $companyId) {
            throw new InvalidArgumentException('That customer does not belong to this company.');
        }

        return $customer;
    }

    /** Map the request payload onto the order row (both for create and update). */
    protected function orderAttributes(array $data, array $overrides, Customer $customer): array
    {
        return array_merge([
            'branch_id' => $data['branch_id'] ?? null,
            'cost_center_id' => $data['cost_center_id'] ?? null,
            'order_date' => $data['order_date'] ?? now()->toDateString(),
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'reference' => $data['reference'] ?? null,
            'memo' => $data['memo'] ?? null,
            'notes_internal' => $data['notes_internal'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            // Contact snapshot — printed on the sheet, independent of later edits.
            'customer_name' => $customer->name,
            'contact_person' => trim((string) ($data['contact_person'] ?? '')) ?: $customer->display_name,
            'email' => trim((string) ($data['email'] ?? '')) ?: $customer->email,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: $customer->phone,
            'delivery_address' => trim((string) ($data['delivery_address'] ?? '')) ?: $customer->billing_address,
            'delivery_method' => $data['delivery_method'] ?? null,
            'department' => $data['department'] ?? null,
            'prepared_by' => $data['prepared_by'] ?? null,
            'terms_key' => $data['terms_key'] ?? null,
            'source_quotation_id' => $data['source_quotation_id'] ?? null,
        ], $overrides);
    }

    /**
     * Coerce the posted lines array into typed rows, computing the amounts once
     * so the stored row, the totals and the invoice conversion all agree.
     */
    protected function normaliseLines(array $lines): array
    {
        $out = [];
        $position = 0;

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $quantity = round((float) ($line['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($line['unit_price'] ?? 0), 2);
            $discount = round((float) ($line['discount'] ?? 0), 2);
            $taxRate = round((float) ($line['tax_rate'] ?? 0), 2);

            $net = round($quantity * $unitPrice - $discount, 2);
            $tax = round($net * $taxRate / 100, 2);

            $itemCode = trim((string) ($line['item_code'] ?? ''));
            $itemName = trim((string) ($line['item_name'] ?? ''));
            $description = trim((string) ($line['description'] ?? ''));

            $out[] = [
                'position' => $position++,
                'product_id' => $line['product_id'] ?? null,
                'item_code' => $itemCode !== '' ? $itemCode : null,
                'item_name' => $itemName !== '' ? $itemName : null,
                // Description is optional; the label falls back to item/description.
                'description' => $description !== ''
                    ? $description
                    : trim(($itemCode !== '' ? $itemCode . ' — ' : '') . ($itemName ?: 'Item')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'amount' => $net,
                'tax_amount' => $tax,
                'line_total' => round($net + $tax, 2),
                'income_account_id' => $line['income_account_id'] ?? null,
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ];
        }

        return $out;
    }

    /** @return array{0:float,1:float} subtotal, tax total */
    protected function totals(array $lines): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += (float) $line['amount'];
            $taxTotal += (float) $line['tax_amount'];
        }

        return [round($subtotal, 2), round($taxTotal, 2)];
    }

    protected function lineAmount(array $line): float
    {
        $quantity = (float) ($line['quantity'] ?? 0);
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $discount = (float) ($line['discount'] ?? 0);

        return round($quantity * $unitPrice - $discount, 2);
    }

    protected function syncLines(SalesOrder $order, array $lines): void
    {
        // A draft has no fulfilment history, so a full replace is safe here.
        $order->lines()->delete();

        foreach ($lines as $line) {
            SalesOrderLine::create($line + ['sales_order_id' => $order->id]);
        }
    }

    /**
     * §5.1 · stock integration.
     *
     * `allocation` (confirm) is a RESERVATION, not a physical movement — goods
     * have not left the warehouse, so nothing is consumed. It is recorded as a
     * stock_movements row of type `reservation` through the query builder so the
     * reservation is visible without corrupting on-hand quantities.
     *
     * `relief` (fulfilment) IS physical and goes through
     * InventoryService::consumeStock(), which consumes the real cost layers and
     * posts the inventory movement. Stock relief must never block the document,
     * so a failure is written to the audit trail instead of aborting the batch.
     */
    protected function movement(int $companyId, SalesOrder $order, SalesOrderLine $line, float $quantity, string $kind, string $memo): void
    {
        if ($quantity == 0.0 || ! $line->product_id) {
            return;
        }

        try {
            if ($kind === 'relief') {
                // Physical: consume the real cost layers first so on-hand and
                // COGS follow. The §8.3 rule says an on-hand overflow shows a
                // warning WITHOUT blocking, so an insufficient-stock throw is
                // captured in the audit trail and the ledger row is still
                // written to keep the delivery note and the ledger in agreement.
                try {
                    app(InventoryService::class)->consumeStock(
                        $companyId,
                        (int) $line->product_id,
                        $order->branch_id ? (int) $order->branch_id : null,
                        abs($quantity),
                        now()->toDateString()
                    );
                } catch (\Throwable $costing) {
                    AuditLog::log(
                        $companyId,
                        null,
                        SalesOrderLine::class,
                        $line->id,
                        'stock_relief_uncosted',
                        null,
                        ['qty' => abs($quantity), 'product_id' => $line->product_id],
                        $costing->getMessage()
                    );
                }

                $this->recordMovement($companyId, $order, $line, $quantity, 'relief', $memo);

                return;
            }

            $this->recordMovement($companyId, $order, $line, $quantity, 'reservation', $memo);
        } catch (\Throwable $e) {
            AuditLog::log(
                $companyId,
                null,
                SalesOrder::class,
                $order->id,
                'stock_movement_failed',
                null,
                ['kind' => $kind, 'qty' => $quantity, 'product_id' => $line->product_id],
                $e->getMessage()
            );
        }
    }

    /**
     * Write one row into the §5.1 `stock_movements` ledger.
     *
     * Guarded on the table existing so a tenant provisioned BEFORE this module
     * (where the migration has not yet been applied through `tenant:migrate`)
     * records its audit trail but silently skips the ledger instead of failing
     * the document.
     */
    protected function recordMovement(int $companyId, SalesOrder $order, SalesOrderLine $line, float $quantity, string $type, string $memo): void
    {
        if (! Schema::connection($this->connectionNameForMovement())->hasTable('stock_movements')) {
            return;
        }

        DB::table('stock_movements')->insert([
            'company_id' => $companyId,
            'product_id' => $line->product_id,
            'branch_id' => $order->branch_id,
            'type' => $type,
            'quantity' => abs($quantity),
            // `signed_quantity` carries the spec's ±qty convention so a report
            // can net reservations against reliefs without re-deriving the sign.
            'signed_quantity' => ($type === 'relief' ? '-' : '+') . number_format(abs($quantity), 2, '.', ''),
            'item_code' => $line->item_code ?: $line->product?->sku,
            'reference' => $type === 'relief' && $order->delivery_note_no
                ? $order->delivery_note_no
                : $order->sales_order_number,
            'reference_type' => SalesOrder::class,
            'reference_id' => $order->id,
            'movement_date' => now()->toDateString(),
            'memo' => $memo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function connectionNameForMovement(): ?string
    {
        return TenantConnectionResolver::connectionName();
    }

    /**
     * Payment-term days for the order's customer, clamped to a sane window so a
     * bad registry value can never produce a far-future or pre-1970 due date.
     * The invoicing module owns the invoice due date; we only inherit the term.
     */
    protected function paymentTermDaysFor(SalesOrder $order): int
    {
        $days = (int) (Customer::find($order->customer_id)?->payment_terms_days ?? 0);

        return max(0, min($days, 365));
    }

    // ---------------------------------------------------------------------
    // Backward-compatible surface
    //
    // The pre-existing `sales-orders/{order}/convert-to-receipt` route (POS
    // sale-off-a-fulfilled-order, §9 integration point) and the older
    // controller verbs still resolve. They delegate to the spec-named methods so
    // there is exactly one implementation of each lifecycle transition.
    // ---------------------------------------------------------------------

    /** @deprecated use release() */
    public function send(SalesOrder $order, int $userId = 0): SalesOrder
    {
        return $this->release($order, $userId);
    }

    /** @deprecated use confirm() */
    public function markFulfilled(SalesOrder $order, int $userId = 0): SalesOrder
    {
        $this->assertStatus($order, SalesOrder::STATUS_CONFIRMED, 'fulfilled');

        $order->update([
            'status' => SalesOrder::STATUS_FULFILLED,
            'delivered_on' => $order->delivered_on ?: now()->toDateString(),
            'delivery_note_no' => $order->delivery_note_no ?: $this->numberingService->getNextNumber((int) $order->company_id, 'delivery_note'),
        ]);

        foreach ($order->lines as $line) {
            $this->movement((int) $order->company_id, $order, $line, (float) $line->quantity, 'relief', 'Delivered against sales order.');
        }

        $this->audit((int) $order->company_id, $order, $userId ?: null, 'fulfilled', SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_FULFILLED, null);

        return $order;
    }

    /** @deprecated use createInvoice() */
    public function convertToInvoice(SalesOrder $order, int $userId): Invoice
    {
        return $this->createInvoice($order, $userId);
    }

    /**
     * POS sale-off-a-fulfilled-order. Preserved from the pre-rebuild surface
     * because the route is already wired and §9 sanctions the integration point.
     */
    public function convertToSalesReceipt(SalesOrder $order, array $paymentData, int $userId): SalesReceipt
    {
        if (! $order->isTerminal() || $order->status !== SalesOrder::STATUS_FULFILLED) {
            throw new InvalidArgumentException('Only fulfilled sales orders can be converted to a sales receipt.');
        }

        if ($order->converted_receipt_id && $receipt = $order->convertedReceipt) {
            return $receipt;
        }

        $order->loadMissing('lines');

        $receipt = app(SalesReceiptService::class)->create([
            'company_id' => $order->company_id,
            'branch_id' => $order->branch_id,
            'cost_center_id' => $order->cost_center_id,
            'customer_id' => $order->customer_id,
            'receipt_date' => now()->toDateString(),
            'reference' => $order->reference,
            'memo' => 'Converted from Sales Order ' . $order->sales_order_number,
            'currency' => $order->currency,
            'lines' => $order->lines->map(fn (SalesOrderLine $l) => [
                'product_id' => $l->product_id,
                'description' => $l->label(),
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'discount' => $l->discount,
                'tax_rate' => $l->tax_rate,
                'income_account_id' => $l->income_account_id,
                'cost_center_id' => $l->cost_center_id,
            ])->toArray(),
            'payments' => $paymentData['payments'] ?? [],
        ], $userId);

        $order->update(['converted_receipt_id' => $receipt->id]);

        $this->audit((int) $order->company_id, $order, $userId, 'converted_to_receipt', null, null, 'Sales receipt ' . $receipt->receipt_number . ' raised.');

        return $receipt;
    }

    /**
     * Drafts are the only deletable state; §5.5 keeps every released/fulfilled
     * document (cancel/void instead), so this stays draft-only and audited.
     */
    public function destroy(SalesOrder $order, int $userId = 0): void
    {
        $this->assertDraft($order, 'deleted');

        $companyId = (int) $order->company_id;
        $number = $order->sales_order_number;

        DB::transaction(function () use ($order) {
            $order->lines()->delete();
            $order->fulfilments()->delete();
            $order->delete();
        });

        AuditLog::log(
            $companyId,
            $userId ?: null,
            SalesOrder::class,
            $order->id,
            'deleted',
            ['status' => SalesOrder::STATUS_DRAFT, 'number' => $number],
            null,
            'Draft sales order ' . $number . ' deleted before release.'
        );
    }

    protected function audit(
        int $companyId,
        SalesOrder $order,
        ?int $userId,
        string $action,
        ?string $from,
        ?string $to,
        ?string $notes
    ): void {
        AuditLog::log(
            $companyId,
            $userId,
            SalesOrder::class,
            $order->id,
            $action,
            $from ? ['status' => $from] : null,
            $to ? ['status' => $to] : null,
            $notes
        );
    }

    protected function assertDraft(SalesOrder $order, string $verb): void
    {
        if (! $order->isDraft()) {
            throw new InvalidArgumentException("Only draft sales orders can be {$verb}.");
        }
    }

    protected function assertStatus(SalesOrder $order, string $status, string $verb): void
    {
        if ($order->status !== $status) {
            throw new InvalidArgumentException("Only {$status} sales orders can be {$verb}.");
        }
    }
}