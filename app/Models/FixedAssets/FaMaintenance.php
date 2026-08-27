<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaMaintenance extends Model
{
    use TenantScoped;

    protected $table = 'fa_maintenance';

    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_UNSCHEDULED = 'unscheduled';
    public const TYPE_REPAIR = 'repair';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'asset_id',
        'type',
        'maintenance_date',
        'next_due_date',
        'provider',
        'cost',
        'description',
        'work_performed',
        'status',
        'requested_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_due_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SCHEDULED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('next_due_date', '>=', now())->orderBy('next_due_date');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('next_due_date', '<', now())->where('status', self::STATUS_COMPLETED);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
