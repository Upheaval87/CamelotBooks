<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Invoice extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'reference',
        'memo',
        'status',
        'amount',
        'amount_paid',
        'settled',
        'currency',
        'exchange_rate',
        'base_amount',
        'journal_entry_id',
        'recurring_template_id',
        'created_by',
        'posted_by',
        'posted_at',
'voided_by',
        'voided_at',
        'void_reason',
        // AR module (INVOICES MODULE 5.1)
        'customer_name',
        'contact_person',
        'email',
        'phone',
        'bill_address',
        'terms_key',
        'customer_ref',
        'source_type',
        'source_ref',
        'source_quotation_id',
        'issue_date',
        'subtotal',
        'tax_total',
        'grand_total',
        'notes_customer',
        'notes_internal',
        'cost_centre',
        'department',
        'prepared_by',
        'emailed_to',
        'emailed_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'issue_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'settled' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_VOID = 'void';

    /**
     * The AR module presents the spec vocabulary (5.1 lists `partial`) while the
     * stored value stays `partially_paid` so the readers outside this module —
     * dashboard, customer centre, statements, sales register, receipts — keep
     * resolving. Nothing outside this model should compare the raw status.
     */
    const AR_STATUS_MAP = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_SENT => 'sent',
        self::STATUS_PARTIALLY_PAID => 'partial',
        self::STATUS_PAID => 'paid',
        self::STATUS_VOID => 'void',
    ];

    const AR_STATUSES = ['draft', 'sent', 'partial', 'paid', 'void'];

    const AR_BUCKETS = ['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus'];

    const SOURCE_MANUAL = 'manual';
    const SOURCE_QUOTATION = 'quotation';
    const SOURCE_SALES_ORDER = 'sales_order';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoiceTemplate::class, 'recurring_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function receiptAllocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }

    /**
     * Legacy settlement leg: the sales-receipt allocation path that predates the
     * AR module. Kept as an alias because `payments` now names the module's own
     * immutable receipts (5.1); nothing in the AR module reads this relation.
     */
    public function settlementPayments(): MorphToMany
    {
        return $this->morphedByMany(CustomerPayment::class, 'payable')
            ->using(CustomerPaymentAllocation::class);
    }

    /** Immutable receipts recorded against this invoice (5.2 payments endpoint). */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function sourceQuotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'source_quotation_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status !== self::STATUS_DRAFT;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->documentTotal() - (float) $this->amount_paid, 2);
    }

    /**
     * Grand total with a legacy fallback: rows written before 5.1 only carry
     * `amount`, so the AR surfaces must never render a blank document total.
     */
    public function documentTotal(): float
    {
        return (float) ($this->grand_total ?? $this->amount);
    }

    public function documentSubtotal(): float
    {
        return (float) ($this->subtotal ?? $this->amount);
    }

    public function documentTax(): float
    {
        return (float) ($this->tax_total ?? 0);
    }

    /** The date the document reads as issued, whichever column holds it. */
    public function issueDate(): ?\Carbon\Carbon
    {
        return $this->issue_date ?: $this->invoice_date;
    }

    /** Party name with the customer-account fallback (walk-in rows have no FK). */
    public function partyName(): string
    {
        return $this->customer?->name ?: ($this->customer_name ?: 'Walk-in Customer');
    }

    /**
     * Presentable status. `overdue` is derived (R6) and never stored, so a row
     * carrying the legacy value is re-derived here instead of trusted.
     */
    public function arStatus(): string
    {
        $stored = $this->status;

        // A legacy row may carry `overdue`. R6 says that value is never stored, so
        // it is treated as *no information at all* about the receipts and the
        // settlement state is recomputed. Mapping it blindly to `sent` would show
        // a fully paid invoice as outstanding in the register and in AR ageing.
        if ($stored === self::STATUS_OVERDUE) {
            $stored = $this->deriveStatusFromReceipts();
        }

        $mapped = self::AR_STATUS_MAP[$stored] ?? 'sent';

        return $this->isOverdue() ? 'overdue' : $mapped;
    }

    /**
     * 5.4 — paid when the receipts cover the document, partial when they cover
     * part of it, sent otherwise. Used to re-derive a status that carries no
     * trustworthy settlement information.
     */
    private function deriveStatusFromReceipts(): string
    {
        $grandTotal = (float) ($this->grand_total ?? 0);

        if ($grandTotal > 0 && (float) $this->balance_due <= 0.005) {
            return self::STATUS_PAID;
        }

        if ((float) $this->amount_paid > 0.005) {
            return self::STATUS_PARTIALLY_PAID;
        }

        return self::STATUS_SENT;
    }

    /** Open-for-collection status, ignoring the derived overdue overlay. */
    public function collectionStatus(): string
    {
        return self::AR_STATUS_MAP[$this->status] ?? 'sent';
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_VOID, self::STATUS_PAID], true)) {
            return false;
        }

        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->balance_due > 0.005;
    }

    /** 5.4: overdue is never stored, so a queue or report derives it from here. */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        // Carbon 3's diffInDays is *signed*: `today->diffInDays(due_date)` returns a
        // NEGATIVE number for the past-dated documents we care about here. Ageing
        // is a magnitude, so the sign has to be taken out or every AR bucket and
        // every queue sort lands in the wrong band.
        return abs((int) $this->due_date->startOfDay()->diffInDays(now()->startOfDay()));
    }

    /** 5.4 AR/aging buckets: current, 1-30, 31-60, 61-90, 90+. */
    public function agingBucket(): string
    {
        $days = $this->daysOverdue();

        return match (true) {
            $days <= 0 => 'current',
            $days <= 30 => 'days_1_30',
            $days <= 60 => 'days_31_60',
            $days <= 90 => 'days_61_90',
            default => 'days_90_plus',
        };
    }

    /** True once the receipts fully cover the document (5.4 "paid"). */
    public function isSettled(): bool
    {
        return $this->balance_due <= 0.005;
    }

    public function isAwaitingEmail(): bool
    {
        return $this->emailed_at === null;
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIALLY_PAID, self::STATUS_OVERDUE])
            ->whereColumn('amount_paid', '<', 'amount');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('invoice_date', [$from, $to]);
    }
}
