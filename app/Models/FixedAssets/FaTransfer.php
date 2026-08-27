<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaTransfer extends Model
{
    use TenantScoped;

    protected $table = 'fa_transfers';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'company_id',
        'asset_id',
        'transfer_date',
        'from_branch_id',
        'to_branch_id',
        'from_cost_center_id',
        'to_cost_center_id',
        'from_custodian',
        'to_custodian',
        'from_location',
        'to_location',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class, 'to_branch_id');
    }

    public function fromCostCenter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CostCenter::class, 'from_cost_center_id');
    }

    public function toCostCenter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CostCenter::class, 'to_cost_center_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
