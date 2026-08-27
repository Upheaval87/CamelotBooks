<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaWarranty extends Model
{
    use TenantScoped;

    protected $table = 'fa_warranty';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'asset_id',
        'provider',
        'warranty_number',
        'start_date',
        'end_date',
        'terms',
        'contact_info',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>=', now());
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->end_date, false);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
