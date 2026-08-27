<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaDepBook extends Model
{
    use TenantScoped;

    protected $table = 'fa_dep_books';

    public const BOOK_FINANCIAL = 'financial';
    public const BOOK_TAX = 'tax';

    protected $fillable = [
        'company_id',
        'asset_id',
        'book_type',
        'depreciation_method',
        'useful_life',
        'residual_value',
        'depreciation_rate',
        'cost',
        'accumulated_depreciation',
        'net_book_value',
        'last_run_date',
        'is_active',
    ];

    protected $casts = [
        'useful_life' => 'integer',
        'residual_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:4',
        'cost' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'last_run_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeFinancial(Builder $query): Builder
    {
        return $query->where('book_type', self::BOOK_FINANCIAL);
    }

    public function scopeTax(Builder $query): Builder
    {
        return $query->where('book_type', self::BOOK_TAX);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function isFinancial(): bool
    {
        return $this->book_type === self::BOOK_FINANCIAL;
    }

    public function isTax(): bool
    {
        return $this->book_type === self::BOOK_TAX;
    }

    public function depreciableAmount(): float
    {
        return (float) $this->cost - (float) $this->residual_value;
    }

    public function depreciationRemaining(): float
    {
        return max(0, $this->depreciableAmount() - (float) $this->accumulated_depreciation);
    }
}
