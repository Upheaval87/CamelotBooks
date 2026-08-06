<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A company-side request to raise their branch_limit (see the matching tenant
 * migration for the schema rationale). TenantScoped because the request lives
 * in the tenant database; legacy shared-DB companies write to the shared DB.
 */
class BranchRequest extends Model
{
    use TenantScoped;

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses during which the company is barred from submitting another request. */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_QUOTED,
        self::STATUS_AWAITING_PAYMENT,
    ];

    protected $fillable = [
        'company_id',
        'branch_name',
        'branch_code',
        'branch_address',
        'contact_person',
        'contact_email',
        'contact_phone',
        'requested_quantity',
        'status',
        'reason',
        'admin_notes',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'requested_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(BillingQuotation::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            BranchPayment::class,
            BillingQuotation::class,
            'branch_request_id',
            'billing_quotation_id'
        );
    }

    public function hasOpenRequest(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_QUOTED => 'Quoted',
            self::STATUS_AWAITING_PAYMENT => 'Awaiting Payment',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }
}
