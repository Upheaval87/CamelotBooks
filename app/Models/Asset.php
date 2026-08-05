<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Asset extends Model
{
    use TenantScoped;

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    const STATUS_DISPOSED = 'disposed';
    const STATUS_WRITTEN_OFF = 'written_off';
    const STATUS_FULLY_DEPRECIATED = 'fully_depreciated';

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'category_id',
        'asset_code',
        'name',
        'description',
        'serial_number',
        'acquisition_date',
        'in_service_date',
        'acquisition_cost',
        'residual_value',
        'useful_life',
        'depreciation_method_financial',
        'depreciation_method_tax',
        'useful_life_tax',
        'residual_value_tax',
        'depreciation_rate_tax',
        'is_revaluation_enabled',
        'status',
        'is_active',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'accumulated_impairment_account_id',
        'impairment_loss_account_id',
        'disposal_gain_loss_account_id',
        'revaluation_surplus_account_id',
        'acquisition_source_type',
        'acquisition_source_id',
        'vendor_id',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'in_service_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'useful_life' => 'integer',
        'useful_life_tax' => 'integer',
        'residual_value_tax' => 'decimal:2',
        'depreciation_rate_tax' => 'decimal:4',
        'is_revaluation_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function accumulatedImpairmentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_impairment_account_id');
    }

    public function impairmentLossAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'impairment_loss_account_id');
    }

    public function disposalGainLossAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'disposal_gain_loss_account_id');
    }

    public function revaluationSurplusAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revaluation_surplus_account_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function depreciationBooks(): HasMany
    {
        return $this->hasMany(AssetDepreciationBook::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function impairments(): HasMany
    {
        return $this->hasMany(AssetImpairment::class);
    }

    public function revaluations(): HasMany
    {
        return $this->hasMany(AssetRevaluation::class);
    }

    public function usageEntries(): HasMany
    {
        return $this->hasMany(UnitsOfProductionUsageEntry::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }

    public function getNetBookValueAttribute(): float
    {
        return (float) $this->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->sum('net_book_value');
    }

    public function getAccumulatedDepreciationAttribute(): float
    {
        return (float) $this->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->sum('accumulated_depreciation');
    }

    public function getAccumulatedImpairmentAttribute(): float
    {
        return (float) $this->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->sum('accumulated_impairment');
    }

    public function resolveFinancialAccount(string $field): ?Account
    {
        $assetAccountId = $this->{$field};
        if ($assetAccountId) {
            return Account::find($assetAccountId);
        }

        return $this->category?->{$field . '_account'};
    }

    public function resolveTaxAccount(string $field): ?Account
    {
        $assetAccountId = $this->{$field};
        if ($assetAccountId) {
            return Account::find($assetAccountId);
        }

        return $this->category?->{$field . '_account'};
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeActiveStatus(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDisposed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DISPOSED);
    }
}
