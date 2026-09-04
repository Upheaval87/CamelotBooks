<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single source of truth for the lifecycle state of each (company, tax_type,
 * period) tax obligation.
 *
 * The status constants below are the ONLY place obligation statuses are defined.
 * Every service/controller gate compares against these constants — never against
 * inline lowercase/uppercase literals — and child entity statuses
 * (tax_periods.status, tax_returns.status, tax_payments.status) are kept in
 * lockstep with this row by TaxObligationService.
 */
class TaxObligation extends Model
{
    use TenantScoped;

    // Upstream lifecycle states.
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CALCULATING = 'CALCULATING';
    public const STATUS_READY_TO_RECONCILE = 'READY_TO_RECONCILE';
    public const STATUS_RECONCILED = 'RECONCILED';
    public const STATUS_RETURN_DRAFTED = 'RETURN_DRAFTED';
    public const STATUS_RETURN_APPROVED = 'RETURN_APPROVED';
    public const STATUS_FILED = 'FILED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CLOSED = 'CLOSED';

    // Side state reachable from RETURN_DRAFTED / RETURN_APPROVED.
    public const STATUS_REJECTED = 'REJECTED';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CALCULATING,
        self::STATUS_READY_TO_RECONCILE,
        self::STATUS_RECONCILED,
        self::STATUS_RETURN_DRAFTED,
        self::STATUS_RETURN_APPROVED,
        self::STATUS_FILED,
        self::STATUS_PAID,
        self::STATUS_CLOSED,
        self::STATUS_REJECTED,
    ];

    // Statuses that are still "in flight" (open, being calculated/reconciled).
    public const ACTIVE_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_CALCULATING,
        self::STATUS_READY_TO_RECONCILE,
        self::STATUS_RECONCILED,
        self::STATUS_RETURN_DRAFTED,
        self::STATUS_RETURN_APPROVED,
        self::STATUS_FILED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'company_id',
        'tax_type_id',
        'period_id',
        'status',
        'blocked_reason',
        'variance_waived',
        'variance_waived_reason',
        'variance_waived_by',
        'variance_waived_at',
        'nil_or_refund_flag',
        'nil_or_refund_reason',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'variance_waived' => 'boolean',
        'variance_waived_reason' => 'string',
        'nil_or_refund_flag' => 'boolean',
        'variance_waived_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeOrderedByDue(Builder $q): Builder
    {
        return $q->orderBy('status')
            ->orderBy('created_at');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /**
     * True when the obligation has passed its filing due date and is not closed.
     */
    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_CLOSED
            && $this->period?->filing_due_date
            && $this->period->filing_due_date->isPast();
    }

    public function requiresVarianceReleaseBeforeReconcile(): bool
    {
        return $this->status === self::STATUS_READY_TO_RECONCILE;
    }

    public function isReconciled(): bool
    {
        return $this->status === self::STATUS_RECONCILED;
    }

    public function statusIsApprovedOnward(): bool
    {
        return in_array($this->status, [
            self::STATUS_RETURN_APPROVED,
            self::STATUS_FILED,
            self::STATUS_PAID,
            self::STATUS_CLOSED,
        ], true);
    }
}