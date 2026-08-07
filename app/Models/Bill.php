<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Bill extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'vendor_id',
        'purchase_order_id',
        'grn_id',
        'bill_number',
        'internal_number',
        'po_number',
        'grn_reference',
        'bill_date',
        'due_date',
        'reference',
        'memo',
        'supplier_notes',
        'payment_instructions',
        'status',
        'amount',
        'amount_paid',
        'currency',
        'exchange_rate',
        'freight_charges',
        'insurance_charges',
        'customs_charges',
        'other_charges',
        'base_amount',
        'journal_entry_id',
        'recurring_template_id',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'freight_charges' => 'decimal:2',
        'insurance_charges' => 'decimal:2',
        'customs_charges' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_VOID = 'void';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringBillTemplate::class, 'recurring_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function payments(): MorphToMany
    {
        return $this->morphedByMany(VendorPayment::class, 'payable')
            ->using(VendorPaymentAllocation::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->amount - (float) $this->amount_paid;
    }

    public function totalCharges(): float
    {
        return round(
            (float) $this->freight_charges
            + (float) $this->insurance_charges
            + (float) $this->customs_charges
            + (float) $this->other_charges,
            2
        );
    }

    public function getSubtotalAttribute(): float
    {
        return round((float) $this->lines->sum('amount'), 2);
    }

    public function getTaxTotalAttribute(): float
    {
        return round((float) $this->lines->sum('tax_amount'), 2);
    }

    public function getTotalAttribute(): float
    {
        return round((float) $this->subtotal + (float) $this->tax_total + $this->totalCharges(), 2);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
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
        return $query->whereBetween('bill_date', [$from, $to]);
    }
}
