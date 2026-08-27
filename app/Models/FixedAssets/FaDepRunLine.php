<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaDepRunLine extends Model
{
    use TenantScoped;

    protected $table = 'fa_dep_run_lines';

    public const STATUS_POSTED = 'posted';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'company_id',
        'run_id',
        'asset_id',
        'dep_book_id',
        'book_type',
        'opening_nbv',
        'depreciation_amount',
        'closing_nbv',
        'status',
        'skip_reason',
    ];

    protected $casts = [
        'opening_nbv' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
        'closing_nbv' => 'decimal:2',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(FaDepRun::class, 'run_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function depBook(): BelongsTo
    {
        return $this->belongsTo(FaDepBook::class, 'dep_book_id');
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }
}
