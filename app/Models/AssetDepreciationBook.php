<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDepreciationBook extends Model
{
    use TenantScoped;

    const BOOK_FINANCIAL = 'financial';
    const BOOK_TAX = 'tax';

    protected $fillable = [
        'asset_id',
        'book_type',
        'depreciation_method',
        'useful_life',
        'residual_value_type',
        'residual_value',
        'depreciation_rate',
        'total_estimated_units',
        'sum_of_years_digits',
        'current_cost',
        'accumulated_depreciation',
        'accumulated_impairment',
        'net_book_value',
        'last_depreciation_date',
        'status',
    ];

    protected $casts = [
        'useful_life' => 'integer',
        'residual_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:4',
        'total_estimated_units' => 'decimal:2',
        'sum_of_years_digits' => 'integer',
        'current_cost' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'accumulated_impairment' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'last_depreciation_date' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(DepreciationScheduleEntry::class, 'asset_depreciation_book_id');
    }
}
