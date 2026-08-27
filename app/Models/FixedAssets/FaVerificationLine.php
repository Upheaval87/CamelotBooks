<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaVerificationLine extends Model
{
    use TenantScoped;

    protected $table = 'fa_verification_lines';

    public const CONDITION_GOOD = 'good';
    public const CONDITION_FAIR = 'fair';
    public const CONDITION_POOR = 'poor';
    public const CONDITION_MISSING = 'missing';

    protected $fillable = [
        'company_id',
        'verification_id',
        'asset_id',
        'is_verified',
        'condition',
        'actual_location',
        'actual_custodian',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopeWithVariance(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_verified', false)
                ->orWhere('condition', self::CONDITION_POOR)
                ->orWhere('condition', self::CONDITION_MISSING)
                ->orWhereColumn('actual_location', '!=', function ($sub) {
                    $sub->select('location')->from('fa_assets')
                        ->whereColumn('fa_assets.id', 'fa_verification_lines.asset_id');
                });
        });
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(FaVerification::class, 'verification_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function hasVariance(): bool
    {
        return ! $this->is_verified
            || in_array($this->condition, [self::CONDITION_POOR, self::CONDITION_MISSING], true);
    }

    public function getConditionLabelAttribute(): string
    {
        return $this->condition ? ucfirst($this->condition) : '—';
    }
}
