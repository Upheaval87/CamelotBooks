<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quotation extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'customer_id',
        'customer_name',
        'contact_person',
        'email',
        'phone',
        'terms_key',
        'department',
        'prepared_by',
        'project',
        'notes_internal',
        'superseded_by',
        'quotation_number',
        'quotation_date',
        'valid_until',
        'reference',
        'memo',
        'status',
        'amount',
        'tax_total',
        'total',
        'currency',
        'journal_entry_id',
        'converted_invoice_id',
        'converted_receipt_id',
        'created_by',
        'posted_by',
        'posted_at',
        'emailed_at',
        'emailed_to',
        'email_status',
        'fail_reason',
        'voided_by',
        'voided_at',
        'void_reason',
        // INVOICES MODULE 5.1 / R13 — invoice picker source of truth
        'invoiced_flag',
        'invoice_id',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'amount' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'posted_at' => 'datetime',
        'emailed_at' => 'datetime',
        'voided_at' => 'datetime',
        'invoiced_flag' => 'boolean',
    ];

    /**
     * §2.1 — dispatch state. `null` means the quotation has never been emailed;
     * `sent` / `failed` are set by the email dispatch pipeline only.
     */
    public function hasEmailed(): bool
    {
        return $this->email_status === 'sent' || ($this->email_status === null && $this->emailed_at !== null);
    }

    public function emailFailed(): bool
    {
        return $this->email_status === 'failed';
    }

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_CONVERTED = 'converted';
    const STATUS_VOID = 'void';

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
        return $this->hasMany(QuotationLine::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }

    /** §2.2 — every email dispatch attempt made for this quotation. */
    public function emailJobs(): MorphMany
    {
        return $this->morphMany(QuotationEmailJob::class, 'quotable');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    /**
     * INVOICES MODULE R13 — the invoice raised from this quotation. The AR
     * module writes `invoiced_flag` + `invoice_id` in the same transaction as
     * the invoice so the picker can never offer an already-converted quote.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function hasBeenInvoiced(): bool
    {
        return (bool) $this->invoiced_flag || $this->invoice_id !== null;
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

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * The quotation that supersedes (re-quoted) this one. §R7 — set on the
     * SOURCE row when a re-quote is created.
     */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'superseded_by');
    }

    /**
     * Derived (never stored) expiry state versus the given server date.
     * §R6 — Expired (red) · ≤7 days (amber) · else Valid (grey).
     */
    public function expiryState(?\DateTimeInterface $today = null): string
    {
        if (!$this->valid_until) {
            return 'none';
        }

        $today = $today ? \Illuminate\Support\Carbon::instance($today) : now();
        $today = $today->copy()->startOfDay();
        $valid = $this->valid_until->copy()->startOfDay();

        if ($valid->lt($today)) {
            return 'expired';
        }
        if ($valid->diffInDays($today) <= 7) {
            return 'soon';
        }

        return 'valid';
    }

    public function daysUntilExpiry(?\DateTimeInterface $today = null): ?int
    {
        if (!$this->valid_until) {
            return null;
        }

        $today = $today ? \Illuminate\Support\Carbon::instance($today) : now();

        return (int) $today->copy()->startOfDay()->diffInDays($this->valid_until->copy()->startOfDay(), false);
    }

    public function isExpired(?\DateTimeInterface $today = null): bool
    {
        return $this->expiryState($today) === 'expired';
    }

    /**
     * Re-quote gate (§R7): expired AND status ∈ {sent, draft, declined}.
     */
    public function isRequotable(?\DateTimeInterface $today = null): bool
    {
        return $this->isExpired($today)
            && in_array($this->status, [self::STATUS_SENT, self::STATUS_DRAFT, self::STATUS_DECLINED], true);
    }
}
