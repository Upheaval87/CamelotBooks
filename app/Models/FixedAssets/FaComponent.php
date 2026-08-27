<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaComponent extends Model
{
    use TenantScoped;

    protected $table = 'fa_components';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETIRED = 'retired';
    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'company_id',
        'asset_id',
        'name',
        'description',
        'cost',
        'accumulated_depreciation',
        'net_book_value',
        'depreciation_method',
        'useful_life',
        'residual_value',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'useful_life' => 'integer',
        'residual_value' => 'decimal:2',
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function depreciableAmount(): float
    {
        return (float) $this->cost - (float) $this->residual_value;
    }
}
