<?php

namespace App\Services\Accounting;

use App\Mail\InvoiceMail;
use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Customer;
use App\Models\DefaultAccountMapping;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * INVOICES MODULE §5 — the AR module's orchestration layer.
 *
 * Division of labour, and why:
 *
 * - `InvoiceService` owns the ledger: line persistence, `amount` accumulation,
 *   journal posting, inventory consumption and the `INV-{YYYY}-{####}` sequence.
 *   Re-implementing any of that here would risk a second, divergent copy of the
 *   accounting math, so this class *delegates* and never posts directly.
 * - This class owns the AR concerns the ledger does not know about: the derived
 *   status/aging state (5.4), immutable receipts with their own `RC-` sequence,
 *   walk-in customer capture (R12), quotation provenance (R13), email dispatch
 *   (R17) and the read models the register and collections queue need (2.1/3.1).
 *
 * Nothing here deletes. Receipts are immutable (5.1) and documents are voided.
 */
class ArInvoiceService
{
    /**
     * The canonical `customers.payment_terms` vocabulary, mirroring
     * `CustomerController`'s validation rule. Anything outside it is stored as
     * `custom` (or `due_on_receipt`) rather than rejected, because a walk-in
     * capture must not be able to abort the invoice that triggered it.
     */
    public const TERMS_KEYS = ['net_15', 'net_30', 'net_60', 'net_90', 'custom', 'due_on_receipt'];

    public function __construct(protected InvoiceService $invoices)
    {
    }

    // ---------------------------------------------------------------- queries

    /**
     * 2.1/2.2 — the register query. `view` selects the saved look (§5.2), the
     * remaining filters are the ones the toolbar exposes.
     *
     * @param  array<string, mixed>  $filters
     */
    public function registerQuery(array $filters, int $companyId): Builder
    {
        $query = Invoice::query()->where('company_id', $companyId);

        if (!empty($filters['q'])) {
            $term = '%' . trim((string) $filters['q']) . '%';

            $query->where(function (Builder $inner) use ($term) {
                $inner->where('invoice_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhere('customer_ref', 'like', $term)
                    ->orWhere('source_ref', 'like', $term);

                // Walk-in invoices have no customer row, so the related name
                // has to be searchable too or they become invisible in search.
                $inner->orWhereHas('customer', function (Builder $customer) use ($term) {
                    $customer->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            });
        }

        if (!empty($filters['status'])) {
            $this->applyStatusFilter($query, (string) $filters['status']);
        }

        // `view` is a mutually-exclusive preset that overrides `status`.
        $view = (string) ($filters['view'] ?? '');
        if ($view !== '') {
            $this->applyViewFilter($query, $view);
        }

        $this->applyDateRange($query, $filters['period_from'] ?? null, $filters['period_to'] ?? null);

        return $query->with(['customer'])->orderByRaw($this->sortClause((string) ($filters['sort'] ?? '')));
    }

    /**
     * Overdue is never stored (5.4), so it cannot be a `where status =` clause —
     * it is a derived expression over the due date and the open balance.
     */
    private function applyStatusFilter(Builder $query, string $status): void
    {
        $today = Carbon::today();

        switch ($status) {
            case 'overdue':
                $query->whereNotIn('status', [
                    Invoice::STATUS_DRAFT,
                    Invoice::STATUS_VOID,
                    Invoice::STATUS_PAID,
                ])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', $today)
                    ->whereRaw('(COALESCE(grand_total, amount) - amount_paid) > 0.005');
                break;

            case 'open':
                $query->whereIn('status', [
                    Invoice::STATUS_SENT,
                    Invoice::STATUS_PARTIALLY_PAID,
                ]);
                break;

            case 'partial':
                $query->where('status', Invoice::STATUS_PARTIALLY_PAID);
                break;

            case '':
                break;

            default:
                $query->where('status', $status);
        }
    }

    private function applyViewFilter(Builder $query, string $view): void
    {
        $this->applyStatusFilter($query, $view);
    }

    private function applyDateRange(Builder $query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->whereDate('invoice_date', '>=', Carbon::parse($from)->toDateString());
        }

        if ($to) {
            $query->whereDate('invoice_date', '<=', Carbon::parse($to)->toDateString());
        }
    }

    /**
     * The single normalizer for `?sort=`: the register's own option values
     * (`new`, `old`, `high`, `bal`, `due`), the legacy list-page aliases
     * (`date-asc`/`amount-desc`/…) and the internal canonical keys all land
     * here, so a filter round-tripped through the URL re-selects the right
     * `<option>` instead of falling back to the default.
     */
    private function sortClause(string $sort): string
    {
        return match ($sort) {
            'amount_desc', 'amount-desc', 'high' => 'COALESCE(grand_total, amount) DESC',
            'amount_asc', 'low' => 'COALESCE(grand_total, amount) ASC',
            'bal' => '(COALESCE(grand_total, amount) - amount_paid) DESC',
            'due_asc', 'due' => 'due_date ASC',
            'due_desc' => 'due_date DESC',
            'old', 'oldest', 'date-asc' => 'invoice_date ASC, id ASC',
            default => 'invoice_date DESC, id DESC',
        };
    }

    public function paginateRegister(array $filters, int $companyId, int $perPage = 25): LengthAwarePaginator
    {
        return $this->registerQuery($filters, $companyId)->paginate($perPage)->withQueryString();
    }

    /**
     * 2.1 — the register KPI tiles. `collection_rate` is collected ÷ billed over
     * non-draft / non-void rows only, so drafts never deflate it and voids
     * never inflate it.
     *
     * @return array<string, float|int>
     */
    public function registerSummary(Builder $query): array
    {
        $rows = (clone $query)
            ->reorder()
            ->get(['status', 'amount', 'grand_total', 'amount_paid', 'due_date', 'invoice_date']);

        $outstanding = 0.0;
        $overdueValue = 0.0;
        $overdueCount = 0;
        $billed = 0.0;
        $collected = 0.0;
        $receiptCount = 0;
        $today = Carbon::today();

        foreach ($rows as $row) {
            $total = (float) ($row->grand_total ?? $row->amount);
            $paid = (float) $row->amount_paid;
            $balance = round($total - $paid, 2);
            $isCounted = !in_array($row->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_VOID], true);

            if ($isCounted) {
                $billed += $total;
                $collected += min($paid, $total);
            }

            if ($row->status === Invoice::STATUS_DRAFT || $row->status === Invoice::STATUS_VOID) {
                continue;
            }

            if ($balance > 0.005) {
                $outstanding += $balance;

                if ($row->due_date && Carbon::parse($row->due_date)->lt($today)) {
                    $overdueValue += $balance;
                    $overdueCount++;
                }
            }
        }

        // Receipt count is a genuine aggregate rather than a derived guess.
        $invoiceIds = (clone $query)->reorder()->pluck('id');
        $receiptCount = InvoicePayment::whereIn('invoice_id', $invoiceIds)->count();

        return [
            'outstanding_ar' => round($outstanding, 2),
            'overdue_count' => $overdueCount,
            'overdue_value' => round($overdueValue, 2),
            'collection_rate' => $billed > 0 ? round($collected / $billed * 100, 1) : 0.0,
            'billed' => round($billed, 2),
            'collected' => round($collected, 2),
            'receipt_count' => $receiptCount,
        ];
    }

    /**
     * 3.1/3.2 — the collections queue: every open balance, worst aging first.
     * `due_within_7` powers the third tile.
     *
     * Aging is derived (5.4), so it cannot be an ORDER BY expression — the rows
     * are hydrated, aged in PHP and then sliced. The queue is bounded by the
     * open-balance count, which the register already caps, so this stays cheap
     * for realistic AR volumes and is the only place that needs the derivation.
     *
     * @return array{invoices: LengthAwarePaginator, totals: array<string, float|int>}
     */
    public function collectionsQueue(Builder $query, int $perPage = 25, int $page = 1): array
    {
        $today = Carbon::today();
        $weekOut = $today->copy()->addDays(7);

        // `STATUS_OVERDUE` is included because legacy rows can still carry it
        // (R6 says overdue is derived and never stored, but the value predates
        // the module). Excluding it here would silently hide those invoices from
        // the one page whose whole job is chasing them.
        $open = (clone $query)
            ->whereIn('status', [
                Invoice::STATUS_SENT,
                Invoice::STATUS_PARTIALLY_PAID,
                Invoice::STATUS_OVERDUE,
            ])
            ->whereRaw('(COALESCE(grand_total, amount) - amount_paid) > 0.005');

        // Worst first: the key is *days past due*, so the most overdue document
        // sorts to the top and an undated balance sinks to the bottom. Carbon 3's
        // `due - today` diff is signed, which is what makes this work: a document
        // 10 days late scores +10 and beats one due in 40 days at -40. Passing
        // the absolute flag here would flatten both to magnitudes and put the
        // not-yet-due document on top of the queue.
        $rows = $open->with('customer')->get()->sortByDesc(
            fn (Invoice $invoice) => $invoice->due_date
                ? $invoice->due_date->startOfDay()->diffInDays($today)
                // An undated open balance sorts after every dated one rather
                // than pretending to be infinitely overdue.
                : PHP_INT_MIN
        )->values();

        $totalOutstanding = 0.0;
        $overdueValue = 0.0;
        $overdueCount = 0;
        $dueSoonValue = 0.0;
        $dueSoonCount = 0;

        foreach ($rows as $invoice) {
            $balance = $invoice->balance_due;
            $totalOutstanding += $balance;

            if ($invoice->isOverdue()) {
                $overdueValue += $balance;
                $overdueCount++;
            } elseif ($invoice->due_date && $invoice->due_date->betweenIncluded($today, $weekOut)) {
                $dueSoonValue += $balance;
                $dueSoonCount++;
            }
        }

        $page = max(1, $page);
        $perPage = max(1, $perPage);

        return [
            'invoices' => new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            ),
            'totals' => [
                'outstanding_ar' => round($totalOutstanding, 2),
                'overdue_value' => round($overdueValue, 2),
                'overdue_count' => $overdueCount,
                'due_7_value' => round($dueSoonValue, 2),
                'due_7_count' => $dueSoonCount,
                'count' => $rows->count(),
            ],
        ];
    }

    // --------------------------------------------------------------- lifecycle

    /**
     * 5.2 — `POST /invoices {mode: draft|post}`. Walk-in capture (R12) happens
     * *inside* the same transaction as the document so a rejected invoice can
     * never leave an orphan customer behind.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId, int $companyId): Invoice
    {
        $mode = ($data['mode'] ?? 'draft') === 'post' ? 'post' : 'draft';

        $invoice = DB::transaction(function () use ($data, $userId, $companyId) {
            $payload = $this->applyCustomerDefaults($data, $companyId);
            $payload = $this->resolveLineAccounts($payload, $companyId);

            if (empty($payload['customer_id']) && !empty($payload['customer_name'])) {
                $payload['customer_id'] = $this->captureWalkInCustomer($payload, $companyId, $userId);
            }

            $this->resolveSource($payload, $companyId);

            return $this->invoices->create($payload, $userId);
        });

        if ($mode === 'post') {
            $invoice = $this->post($invoice, $userId);
        }

        return $invoice->fresh(['lines.product', 'customer']);
    }

    /** 5.2 — `PUT /invoices/{id}`, 409 unless draft. */
    public function update(Invoice $invoice, array $data, int $userId): Invoice
    {
        if (!$invoice->isDraft()) {
            throw new InvalidArgumentException('Only draft invoices can be edited. Posted invoices are immutable — void this one and raise a replacement.');
        }

        $companyId = (int) $invoice->company_id;

        DB::transaction(function () use ($invoice, $data, $userId, $companyId) {
            $payload = $this->applyCustomerDefaults($data, $companyId);
            $payload = $this->resolveLineAccounts($payload, $companyId);

            if (empty($payload['customer_id']) && !empty($payload['customer_name'])) {
                $payload['customer_id'] = $this->captureWalkInCustomer($payload, $companyId, $userId);
            }

            // R19 — the quotation picker is create-only: an edit never silently
            // re-points a document at another source.
            $payload['source_type'] = $invoice->source_type;
            $payload['source_ref'] = $invoice->source_ref;
            $payload['source_quotation_id'] = $invoice->source_quotation_id;

            $this->invoices->update($invoice, $payload, $userId);
        });

        $updated = $invoice->fresh(['lines.product', 'customer']);

        if (($data['mode'] ?? 'draft') === 'post') {
            $updated = $this->post($updated, $userId);
        }

        return $updated->fresh(['lines.product', 'customer']);
    }

    /** 5.2 — `POST /invoices/{id}/post`, 409 unless draft or sent. */
    public function post(Invoice $invoice, int $userId): Invoice
    {
        if (!$invoice->isDraft() && $invoice->status !== Invoice::STATUS_SENT) {
            throw new InvalidArgumentException('Only draft or sent invoices can be posted.');
        }

        // The outer transaction makes the ledger posting, the quotation
        // conversion stamp and the audit row commit together — a converted
        // quotation can never be left dangling against an unposted invoice.
        // `InvoiceService::post()` opens its own transaction, which nests as a
        // savepoint here rather than committing early.
        $posted = DB::transaction(function () use ($invoice, $userId) {
            $posted = $this->invoices->post($invoice, $userId);

            $this->markSourceConverted($posted);

            return $posted;
        });

        // R17 — "posting auto-emails when a valid email exists at post time".
        // A missing or malformed address is deliberately NOT an error here: the
        // document is already posted and ledger-touched, and the operator can
        // still press "Send to customer" once the address is corrected.
        $recipient = trim((string) ($posted->email ?: $posted->customer?->email));

        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->dispatchEmail($posted, $recipient, $userId);
        }

        return $posted->fresh(['lines.product', 'customer']);
    }

    /**
     * 5.2 — `POST /invoices/{id}/void`, 409 when receipts exist. Voiding moves
     * money back through the ledger; with cash already collected against it the
     * correction has to be a credit note, which 5.1 defers to a later table.
     */
    public function void(Invoice $invoice, string $reason, int $userId): Invoice
    {
        if ($invoice->payments()->exists()) {
            throw new InvalidArgumentException('This invoice has recorded payments. Voiding is blocked — raise a credit note instead.');
        }

        return $this->invoices->void($invoice, $reason, $userId)->fresh(['lines.product', 'customer']);
    }

    // --------------------------------------------------------------- receipts

    /**
     * 5.2 — `POST /invoices/{id}/payments`. 422 when the amount exceeds the
     * balance or the split legs do not add up to the amount.
     *
     * 5.4 — the status is recomputed here, from the receipts, in the same
     * transaction as the insert.
     */
    public function recordPayment(Invoice $invoice, array $data, int $userId): InvoicePayment
    {
        if ($invoice->isDraft()) {
            throw new InvalidArgumentException('Payments can only be recorded against a posted invoice.');
        }

        if ($invoice->isVoid()) {
            throw new InvalidArgumentException('This invoice is void and cannot take payments.');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Enter a payment amount greater than zero.');
        }

        $method = (string) ($data['method'] ?? '');
        if (!in_array($method, InvoicePayment::METHODS, true)) {
            throw new InvalidArgumentException('Choose a valid payment method.');
        }

        $details = $this->validateMethodDetails($method, $data['details'] ?? [], $data['splits'] ?? null, $amount);

        return DB::transaction(function () use ($invoice, $data, $userId, $amount, $method, $details) {
            // Re-read under a row lock. The caller's $invoice may be minutes old
            // and another receipt may have landed in between; without this the
            // balance check below would run against a stale figure and two
            // concurrent receipts could over-collect the invoice.
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $balance = $locked->balance_due;

            if ($amount > $balance + 0.005) {
                throw new InvalidArgumentException(
                    'Payment of ' . number_format($amount, 2) . ' exceeds the balance due of ' . number_format($balance, 2) . '.'
                );
            }

            $payment = InvoicePayment::create([
                'company_id' => $locked->company_id,
                'invoice_id' => $locked->id,
                'receipt_no' => $this->generateReceiptNumber((int) $locked->company_id),
                'paid_on' => $data['paid_on'] ?? Carbon::today()->toDateString(),
                'method' => $method,
                'method_label' => $data['method_label'] ?? null,
                'amount' => $amount,
                'allocation_note' => $data['note'] ?? null,
                'details' => $details,
                'splits' => $data['splits'] ?? null,
                'recorded_by' => $userId,
            ]);

            $this->recomputePaymentState($locked->fresh());

            $this->log(Invoice::class, $invoice->id, (int) $invoice->company_id, 'payment_receipted', [
                'amount_paid' => $invoice->amount_paid,
                'status' => $invoice->status,
            ], [
                'receipt_no' => $payment->receipt_no,
                'amount' => $amount,
                'method' => $method,
                'paid_on' => $payment->paid_on?->toDateString(),
                'amount_paid' => $invoice->fresh()->amount_paid,
                'status' => $invoice->fresh()->status,
            ], $userId);

            return $payment;
        });
    }

    /**
     * 8.3 — the per-method required-field gate, enforced server-side so the
     * modal's conditional fields cannot be bypassed by a crafted request.
     *
     * @return array<string, mixed>|null
     */
    private function validateMethodDetails(string $method, array $details, mixed $splits, float $amount): ?array
    {
        $details = array_filter($details, static fn ($value) => $value !== null && $value !== '');

        if ($method === InvoicePayment::METHOD_MOBILE_MONEY && empty($details['phone'])) {
            throw new InvalidArgumentException('Mobile money payments need the payer\'s phone number.');
        }

        if ($method === InvoicePayment::METHOD_CHEQUE) {
            if (empty($details['bank'])) {
                throw new InvalidArgumentException('Cheque payments need the paying bank.');
            }

            if (empty($details['number'])) {
                throw new InvalidArgumentException('Cheque payments need the cheque number.');
            }
        }

        if ($method === InvoicePayment::METHOD_SPLIT) {
            // Each leg is validated against its own method, so a split that
            // hides a phone-less mobile-money leg cannot slip through.
            $this->normaliseSplits($splits, $amount);

            return $details ?: null;
        }

        return $details ?: null;
    }

    /**
     * 5.2 — split legs must add up to the payment amount exactly, to the cent.
     *
     * @return list<array{method: string, amount: float, details: array<string, mixed>}>
     */
    private function normaliseSplits(mixed $splits, float $amount): array
    {
        if (!is_array($splits) || $splits === []) {
            throw new InvalidArgumentException('A split payment needs at least one leg.');
        }

        $legs = [];
        $total = 0.0;

        foreach ($splits as $index => $leg) {
            $method = (string) ($leg['method'] ?? '');
            $legAmount = round((float) ($leg['amount'] ?? 0), 2);

            if (!in_array($method, InvoicePayment::METHODS, true)) {
                throw new InvalidArgumentException("Split leg " . ($index + 1) . ' has an invalid method.');
            }

            if ($method === InvoicePayment::METHOD_SPLIT) {
                throw new InvalidArgumentException("Split leg " . ($index + 1) . ' cannot itself be a split — splits only nest one level.');
            }

            if ($legAmount <= 0) {
                throw new InvalidArgumentException("Split leg " . ($index + 1) . ' needs an amount greater than zero.');
            }

            $legDetails = $this->validateMethodDetails($method, $leg['details'] ?? [], null, $legAmount) ?? [];

            $legs[] = ['method' => $method, 'amount' => $legAmount, 'details' => $legDetails];
            $total += $legAmount;
        }

        if (abs($total - $amount) > 0.005) {
            throw new InvalidArgumentException(
                'Split legs add up to ' . number_format($total, 2) . ' but the payment is ' . number_format($amount, 2) . '.'
            );
        }

        return $legs;
    }

    /**
     * 5.4 — `paid` when the receipts cover the grand total, `partial` when they
     * cover some of it. Reads the receipts rather than trusting `amount_paid`
     * so the two can never disagree after a restore or a legacy backfill.
     */
    public function recomputePaymentState(Invoice $invoice): Invoice
    {
        $paid = (float) $invoice->payments()->sum('amount');

        // `amount_paid`/`settled` are read by the customer centre, statements
        // and the sales register, so they stay in lockstep with the receipts.
        $invoice->update([
            'amount_paid' => round($paid, 2),
            'settled' => round($paid, 2),
            'status' => match (true) {
                $invoice->isVoid() => Invoice::STATUS_VOID,
                $invoice->isDraft() => Invoice::STATUS_DRAFT,
                $paid <= 0.005 => Invoice::STATUS_SENT,
                $paid + 0.005 >= $invoice->documentTotal() => Invoice::STATUS_PAID,
                default => Invoice::STATUS_PARTIALLY_PAID,
            },
        ]);

        return $invoice;
    }

    /**
     * 5.3 — `RC-{YYYY}-{seq4}` per receipt, per-year, per-company.
     *
     * Deliberately a local scan rather than a `NumberingSequence` default: those
     * defaults are seed-count asserted, and a receipt sequence shares no lineage
     * with the sales-receipt module's `REC-` prefix.
     */
    public function generateReceiptNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'RC-' . $year . '-';

        // Lock the company row so two concurrent receipts cannot claim the same
        // sequence — the invoice generator uses the same trick. The query is
        // lazy, so it MUST be terminated (first/value) or no lock is ever
        // taken and both receipts would read the same "last" number.
        DB::table('companies')->where('id', $companyId)->lockForUpdate()->first();

        $last = InvoicePayment::where('company_id', $companyId)
            ->where('receipt_no', 'like', $prefix . '%')
            ->orderByDesc('receipt_no')
            ->first();

        $next = $last ? ((int) substr($last->receipt_no, strlen($prefix)) + 1) : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Non-locking preview of the receipt number the NEXT payment will get.
     * The payment modal shows it read-only (spec paymodal `#pyReceipt`); it is
     * re-derived under the real `lockForUpdate()` at recordPayment() time.
     */
    public function previewReceiptNumber(int $companyId): string
    {
        $prefix = 'RC-' . date('Y') . '-';

        // Must go through the model (TenantScoped -> the bound tenant
        // connection). A raw DB::table() would always hit the default mysql
        // connection and miss invoice_payments in per-tenant DBs.
        $last = InvoicePayment::query()
            ->where('company_id', $companyId)
            ->where('receipt_no', 'like', $prefix . '%')
            ->orderByDesc('receipt_no')
            ->first();

        $next = $last ? ((int) substr($last->receipt_no, strlen($prefix)) + 1) : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------- email

    /**
     * R17 — "Send to customer". The address is validated server-side with the
     * same regex the modal uses, so a crafted request cannot bypass the gate.
     *
     * @return array{emailed: bool, recipient: ?string}
     */
    public function sendEmail(Invoice $invoice, ?int $userId): array
    {
        if ($invoice->isDraft()) {
            throw new InvalidArgumentException('Only sent, partially paid or paid invoices can be emailed.');
        }

        $recipient = trim((string) ($invoice->email ?: $invoice->customer?->email));

        if ($recipient === '') {
            throw new InvalidArgumentException('This invoice has no email address. Add one on the invoice before sending.');
        }

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The customer email address is not valid. Check it on the invoice before sending.');
        }

        // Queued so a slow SMTP peer cannot hold the HTTP request open. The
        // emailed stamp is written optimistically; `InvoiceMail::failed()`
        // retracts it if every delivery attempt fails (R17).
        return $this->dispatchEmail($invoice, $recipient, $userId);
    }

    /**
     * Queues the tax-invoice PDF and records the emailed stamp.
     *
     * Shared by the explicit "Send to customer" action and by the post-time
     * auto-email so both write the same stamp and the same audit action.
     *
     * @return array{emailed: true, recipient: string}
     */
    private function dispatchEmail(Invoice $invoice, string $recipient, ?int $userId): array
    {
        Mail::to($recipient)->queue(new InvoiceMail($invoice));

        $previous = [
            'emailed_to' => $invoice->getOriginal('emailed_to'),
            'emailed_at' => $invoice->getOriginal('emailed_at'),
        ];

        $invoice->update([
            'emailed_to' => $recipient,
            'emailed_at' => now(),
        ]);

        $this->log(Invoice::class, $invoice->id, (int) $invoice->company_id, 'emailed', $previous, [
            'emailed_to' => $recipient,
            'emailed_at' => $invoice->emailed_at?->toIso8601String(),
        ], $userId);

        return ['emailed' => true, 'recipient' => $recipient];
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Fills the document's contact block from the selected customer so the print
     * sheet and the emailed PDF never show a blank address on a registered
     * account. Values already on the payload win.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyCustomerDefaults(array $data, int $companyId): array
    {
        $customer = null;

        if (!empty($data['customer_id'])) {
            $customer = Customer::where('company_id', $companyId)
                ->where('id', $data['customer_id'])
                ->first();
        }

        // Every contact key is read defensively: the controller forwards only the
        // fields the form actually submitted, so a payload may legitimately omit
        // all of them. `?:` on a missing key is a fatal, not a default.
        if ($customer) {
            $data['customer_name'] = ($data['customer_name'] ?? null) ?: $customer->name;
            $data['contact_person'] = ($data['contact_person'] ?? null) ?: $customer->display_name;
            $data['email'] = ($data['email'] ?? null) ?: $customer->email;
            $data['phone'] = ($data['phone'] ?? null) ?: $customer->phone;
            $data['bill_address'] = ($data['bill_address'] ?? null) ?: $customer->billing_address;
            $data['terms_key'] = ($data['terms_key'] ?? null) ?: $customer->payment_terms;
        }

        // 4.3 — the due date follows the selected terms when the caller did not
        // pin one explicitly.
        if (empty($data['due_date']) && !empty($data['issue_date']) && $customer?->payment_terms_days) {
            $data['due_date'] = Carbon::parse($data['issue_date'])
                ->addDays((int) $customer->payment_terms_days)
                ->toDateString();
        }

        // `invoices.due_date` is NOT NULL in the legacy table, so a document with
        // no customer terms and no pinned due date (the ordinary walk-in draft)
        // has to resolve to something. "Due on receipt" is the honest reading and
        // is a valid due date under R3 (≥ issue date), so the post gate still
        // lets it through rather than forcing the operator to invent a term.
        if (empty($data['due_date']) && !empty($data['issue_date'])) {
            $data['due_date'] = Carbon::parse($data['issue_date'])->toDateString();
        }

        return $data;
    }

    /**
     * R12 — a walk-in invoice materialises a real customer row inside the
     * document's transaction, so the name the operator typed is searchable and
     * the AR reports can group by party.
     */
    private function captureWalkInCustomer(array $payload, int $companyId, int $userId): ?int
    {
        $name = trim((string) ($payload['customer_name'] ?? ''));

        if ($name === '') {
            return null;
        }

        // `customers` has no created_by column and no code column, so neither is
        // written here; the invoice carries `customer_ref` if the operator wants
        // a reference on the document itself.
        //
        // `payment_terms` is NOT NULL on `customers`, so a terms key always has
        // to be resolved — an implicit default of net_30 would silently change
        // the ageing of every later invoice raised against this party.
        $terms = (string) ($payload['terms_key'] ?? '');
        $termsDays = $this->walkInTermsDays($payload);

        if ($terms === '' || !in_array($terms, self::TERMS_KEYS, true)) {
            $terms = $termsDays > 0 ? 'custom' : 'due_on_receipt';
        }

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => $name,
            'display_name' => $payload['contact_person'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'billing_address' => $payload['bill_address'] ?? null,
            'payment_terms' => $terms,
            'payment_terms_days' => $termsDays,
            'is_active' => true,
        ]);

        $this->log(Customer::class, $customer->id, $companyId, 'walk_in_customer_captured', null, [
            'customer_name' => $name,
            'source' => 'invoice_walk_in',
        ], $userId);

        return $customer->id;
    }

    /**
     * The walk-in party's terms in days, derived from the document's own window.
     *
     * R3 lets the operator pin any valid due date on a walk-in invoice, so the
     * due-date gap is the most faithful record of the terms that were actually
     * agreed — better than inventing a default bucket.
     *
     * @param  array<string, mixed>  $payload
     */
    private function walkInTermsDays(array $payload): int
    {
        $issue = $payload['issue_date'] ?? null;
        $due = $payload['due_date'] ?? null;

        if (empty($issue) || empty($due)) {
            return 0;
        }

        try {
            $days = (int) Carbon::parse($due)->startOfDay()
                ->diffInDays(Carbon::parse($issue)->startOfDay());
        } catch (\Throwable) {
            return 0;
        }

        // A due date before the issue date is rejected upstream (R3); guard the
        // sign here anyway so a bad payload can never produce a negative term.
        return max(0, $days);
    }

    /**
     * R13 — a converted quotation leaves the picker. The flag and the FK are
     * written here, in the same transaction as the invoice, which is why the
     * picker reads `invoiced_flag` rather than the reverse `invoice_id`.
     */
    /**
     * 5.2 — the spec's create form posts manual lines (item code, item name,
     * description, qty, unit price); the GL account is resolved server-side so
     * a typed code can never point the ledger at a foreign or non-income
     * account.
     *
     * Precedence per line: an already-posted account id (edit prefill, which
     * must not silently drift) → the matched product's effective income account
     * (product > category default) → the `default_revenue` mapping → the first
     * active income account. `tax_rate` keeps whatever the client pinned (edit
     * and quotation prefill carry real rates) and falls back to the spec's
     * fixed 16.5% only when nothing was sent — new blank rows post no tax
     * input, so their rate arrives as null/''.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolveLineAccounts(array $payload, int $companyId): array
    {
        if (empty($payload['lines']) || !is_array($payload['lines'])) {
            return $payload;
        }

        $productIds = [];
        $skus = [];
        foreach ($payload['lines'] as $line) {
            if (!empty($line['product_id'])) {
                $productIds[] = (int) $line['product_id'];
            } elseif (!empty($line['item_code'])) {
                $skus[] = trim((string) $line['item_code']);
            }
        }

        $products = collect();
        if ($productIds || $skus) {
            $products = Product::where('company_id', $companyId)
                ->where(function (Builder $query) use ($productIds, $skus) {
                    $query->whereIn('id', array_unique($productIds))
                        ->orWhereIn('sku', array_unique($skus));
                })
                ->get();
        }

        $fallbackAccountId = DefaultAccountMapping::getAccountId($companyId, 'default_revenue')
            ?? Account::where('company_id', $companyId)
                ->where('type', 'income')
                ->active()
                ->orderBy('code')
                ->value('id');

        if (!$fallbackAccountId) {
            throw new InvalidArgumentException('No income account is configured for this company — set the default revenue account in Settings → Accounts.');
        }

        foreach ($payload['lines'] as $i => $line) {
            $product = null;
            if (!empty($line['product_id'])) {
                $product = $products->firstWhere('id', (int) $line['product_id']);
            } elseif (!empty($line['item_code'])) {
                $product = $products->firstWhere('sku', trim((string) $line['item_code']));
            }

            $accountId = $line['income_account_id'] ?? null;
            if (empty($accountId)) {
                $accountId = $product?->effective_income_account_id ?? $fallbackAccountId;
            }

            $taxRate = $line['tax_rate'] ?? null;

            $payload['lines'][$i]['income_account_id'] = $accountId;
            $payload['lines'][$i]['tax_rate'] = ($taxRate === null || $taxRate === '') ? 16.5 : $taxRate;
            // A code-typed line still records which product it billed, so
            // reports and inventory recognise it (R11's code is the join key).
            $payload['lines'][$i]['product_id'] = !empty($line['product_id'])
                ? (int) $line['product_id']
                : ($product?->id ?? null);
            $payload['lines'][$i]['item_code'] = $line['item_code'] ?? $product?->sku ?? null;
            $payload['lines'][$i]['item_name'] = $line['item_name'] ?? $product?->name ?? null;
        }

        return $payload;
    }

    private function resolveSource(array &$payload, int $companyId): void
    {
        $quotationId = $payload['source_quotation_id'] ?? null;

        if (empty($quotationId)) {
            // A payload without a source key is the ordinary manual case, so the
            // key is read defensively rather than assumed to be present. The
            // create form's "Source document" text is typed on manual invoices
            // too, so it is preserved (an absent key leaves `arColumns`'s
            // tri-state untouched; an empty string still clears).
            $payload['source_type'] = ($payload['source_type'] ?? null) ?: Invoice::SOURCE_MANUAL;
            if (array_key_exists('source_ref', $payload)) {
                $payload['source_ref'] = $payload['source_ref'] ?: null;
            }

            return;
        }

        $quotation = Quotation::where('company_id', $companyId)
            ->where('id', $quotationId)
            ->first();

        if (!$quotation) {
            throw new InvalidArgumentException('The selected quotation no longer exists.');
        }

        if ($quotation->hasBeenInvoiced()) {
            throw new InvalidArgumentException('Quotation ' . $quotation->quotation_number . ' has already been invoiced.');
        }

        $payload['source_type'] = Invoice::SOURCE_QUOTATION;
        $payload['source_ref'] = $quotation->quotation_number;
    }

    private function markSourceConverted(Invoice $invoice): void
    {
        if ($invoice->source_type !== Invoice::SOURCE_QUOTATION || !$invoice->source_quotation_id) {
            return;
        }

        Quotation::where('id', $invoice->source_quotation_id)->update([
            'invoiced_flag' => true,
            'invoice_id' => $invoice->id,
            // The legacy conversion column is kept in step for the quotations
            // module, which still renders "Converted" from it.
            'converted_invoice_id' => $invoice->id,
        ]);

        $this->log(Invoice::class, $invoice->id, (int) $invoice->company_id, 'quotation_converted', null, [
            'quotation_number' => $invoice->source_ref,
            'invoice_number' => $invoice->invoice_number,
            'invoice_id' => $invoice->id,
        ], $invoice->posted_by ?? $invoice->created_by);
    }

    /**
     * 5.5 — every AR mutation lands in the ledger audit trail.
     *
     * Deliberately `account_audit_logs`, the same trail
     * `InvoiceService::logInvoiceAction()` writes, so an invoice's full history
     * (created → posted → receipted → emailed) is one ordered stream rather than
     * two parallel ones. `QuotationMail` uses the general `AuditLog` table
     * because that is where the quotations module records its trail.
     *
     * The `journalable_*` pair is deliberately parameterised: a walk-in capture
     * is audited against the *customer* it created, because at that point no
     * invoice row exists yet. Both columns are NOT NULL in the schema.
     */
    private function log(string $auditableType, int $auditableId, int $companyId, string $action, ?array $old, ?array $new, ?int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $companyId,
            'journalable_type' => $auditableType,
            'journalable_id' => $auditableId,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * 5.1 — `invoiced_flag` is the picker source of truth, so the create page's
     * quotation list is filtered server-side rather than in the browser.
     *
     * Returns a Collection (not a plain array) so callers can read the models'
     * dates and totals directly; the declared type used to be `array` while the
     * body returned a Collection, which is a fatal the moment the picker is wired
     * up — and a `php -l` check cannot see it.
     *
     * @return Collection<int, Quotation>
     */
    public function uninvoicedQuotations(int $companyId, string $search = ''): Collection
    {
        return Quotation::where('company_id', $companyId)
            ->where('invoiced_flag', false)
            ->where(function (Builder $query) use ($search) {
                $query->whereNull('invoice_id')
                    ->orWhere('invoice_id', 0);
            })
            ->whereIn('status', [Quotation::STATUS_ACCEPTED, Quotation::STATUS_SENT])
            ->when($search !== '', function (Builder $query) use ($search) {
                $term = '%' . $search . '%';
                $query->where(function (Builder $inner) use ($term) {
                    $inner->where('quotation_number', 'like', $term)
                        ->orWhere('customer_name', 'like', $term);
                });
            })
            ->orderByDesc('quotation_date')
            ->limit(50)
            ->get(['id', 'quotation_number', 'customer_name', 'quotation_date', 'total', 'status']);
    }

    /**
     * 8.3 — the server-side Post gate, so the rail's check row and the Post
     * button can never disagree: name + due date + one complete line.
     *
     * @return array{allowed: bool, checks: array<string, array{label: string, ok: bool}>}
     */
    public function postGate(Invoice $invoice): array
    {
        $hasLine = $invoice->lines->isNotEmpty();

        // NOT `partyName()`: that helper falls back to "Walk-in Customer" so a
        // blank document still renders a presentable name, which would make
        // this gate permanently green. The gate needs the raw party.
        $party = trim((string) ($invoice->customer?->name ?: $invoice->customer_name));

        $checks = [
            'customer' => [
                'label' => 'Customer named',
                'ok' => $party !== '',
            ],
            'due_date' => [
                'label' => 'Due date set',
                'ok' => $invoice->due_date !== null,
            ],
            'lines' => [
                'label' => 'Complete line',
                'ok' => $hasLine,
            ],
        ];

        return [
            'allowed' => !in_array(false, array_column($checks, 'ok'), true),
            'checks' => $checks,
        ];
    }

    /** R10 — the line-label parser used when a draft is re-opened for editing. */
    public static function parseLineLabel(string $code, string $name, ?string $description): array
    {
        $label = trim(implode(' — ', array_filter([$code, $name])));

        if ($description && !str_starts_with($description, $label)) {
            $description = Str::after($description, $label);
            $description = ltrim($description, ' —-');
        }

        return [
            'item_code' => $code,
            'item_name' => $name,
            'description' => $description,
        ];
    }

    /**
     * Only used by the print sheet and the emailed PDF.
     */
    public function linesForPrint(Invoice $invoice): string
    {
        return $invoice->lines
            ->sortBy(fn (InvoiceLine $line) => $line->position ?? $line->id)
            ->map(fn (InvoiceLine $line) => trim(implode(' — ', array_filter([
                $line->item_code,
                $line->item_name,
                $line->description,
            ]))))
            ->join(', ');
    }
}