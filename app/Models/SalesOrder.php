<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'customer_id',
        'sales_order_number',
        'order_date',
        'expected_delivery_date',
        'reference',
        'memo',
        'status',
        'amount',
        'tax_total',
        'total',
        'currency',
        'converted_invoice_id',
        'converted_receipt_id',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
        // ── full-module columns (2026_12_08_000001) ──
        'source_quotation_id',
        'customer_name',
        'contact_person',
        'email',
        'phone',
        'delivery_address',
        'delivery_method',
        'department',
        'prepared_by',
        'terms_key',
        'notes_internal',
        'delivered_on',
        'delivery_note_no',
        'received_by',
        'emailed_to',
        'emailed_at',
        'cancel_reason',
        'sent_at',
        'confirmed_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'amount' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
        'delivered_on' => 'date',
        'emailed_at' => 'datetime',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_VOID = 'void';

    /**
     * R8 · The status machine. draft→sent (release); sent→confirmed (confirm +
     * stock allocation); confirmed→fulfilled (fulfilment covers ALL line qty);
     * draft|sent|confirmed→cancelled; draft|sent→void. fulfilled is terminal
     * apart from the invoice link, which is not a status.
     */
    public const LIFECYCLE = ['draft', 'sent', 'confirmed', 'fulfilled'];

    public const OPEN_STATUSES = ['draft', 'sent', 'confirmed'];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SENT => 'Sent',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_FULFILLED => 'Fulfilled',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_VOID => 'Void',
    ];

    /** The four status pills the register renders, plus the two terminal ones. */
    public const STATUS_CLASSES = [
        self::STATUS_DRAFT => 'dr',
        self::STATUS_SENT => 'se',
        self::STATUS_CONFIRMED => 'cf',
        self::STATUS_FULFILLED => 'fu',
        self::STATUS_CANCELLED => 'ca',
        self::STATUS_VOID => 'vo',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function convertedReceipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'converted_receipt_id');
    }

    public function createdByUser(): BelongsTo
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

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_VOID], true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusClass(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? 'dr';
    }

    /** Warehouse is the Branch this order is fulfilled from (R12/R13). */
    public function warehouse(): BelongsTo
    {
        return $this->branch();
    }

    public function sourceQuotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'source_quotation_id');
    }

    /** §5.1 partial-delivery audit trail. */
    public function fulfilments(): HasMany
    {
        return $this->hasMany(OrderFulfilment::class);
    }

    /**
     * R12 · Ordered-vs-fulfilled roll-up used by the register progress bar, the
     * view modal's "x/y qty fulfilled" chip and the delivery-note sheet.
     *
     * @return array{qty_ordered:float,qty_fulfilled:float,remaining:float,pct:int,partial:bool,complete:bool}
     */
    public function fulfilmentProgress(): array
    {
        $ordered = 0.0;
        $done = 0.0;

        foreach ($this->relationLoaded('lines') ? $this->lines : $this->lines()->get() as $line) {
            $ordered += (float) $line->quantity;
            $done += (float) ($line->qty_fulfilled ?? 0);
        }

        $ordered = round($ordered, 2);
        $done = round(min($done, $ordered), 2);
        $remaining = round(max($ordered - $done, 0), 2);

        return [
            'qty_ordered' => $ordered,
            'qty_fulfilled' => $done,
            'remaining' => $remaining,
            'pct' => $ordered > 0 ? (int) round($done / $ordered * 100) : 0,
            'partial' => $done > 0 && $remaining > 0,
            'complete' => $ordered > 0 && $remaining <= 0,
        ];
    }

    /**
     * R7 · Delivery intelligence computed against the server's today. The
     * register chip, the KPI tile and the due-for-delivery rail all read this
     * one method so they can never disagree.
     *
     * @return array{key:string,label:string}
     */
    public function deliveryChip(?\DateTimeInterface $today = null): array
    {
        if ($this->isTerminal()) {
            return ['key' => 'none', 'label' => '—'];
        }

        if ($this->status === self::STATUS_FULFILLED) {
            return ['key' => 'good', 'label' => 'Delivered'];
        }

        $due = $this->expected_delivery_date;
        if (! $due) {
            return ['key' => 'ok', 'label' => 'Scheduled'];
        }

        $today = $today ? \Carbon\Carbon::parse($today) : now();
        $days = (int) $today->startOfDay()->diffInDays($due->copy()->startOfDay(), false);

        if ($days < 0) {
            $late = abs($days);

            return ['key' => 'exp', 'label' => $late === 1 ? '1 day late' : "{$late} days late"];
        }

        if ($days === 0) {
            return ['key' => 'soon', 'label' => 'Today'];
        }

        if ($days <= 7) {
            return ['key' => 'soon', 'label' => $days . ($days === 1 ? ' day' : ' days')];
        }

        return ['key' => 'ok', 'label' => 'Scheduled'];
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }
}
