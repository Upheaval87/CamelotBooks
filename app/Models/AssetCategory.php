<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'depreciation_method_financial',
        'useful_life_financial',
        'residual_value_type_financial',
        'residual_value_financial',
        'depreciation_method_tax',
        'useful_life_tax',
        'residual_value_type_tax',
        'residual_value_tax',
        'depreciation_rate_tax',
        'is_revaluation_enabled',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'accumulated_impairment_account_id',
        'impairment_loss_account_id',
        'disposal_gain_loss_account_id',
        'revaluation_surplus_account_id',
        'is_active',
    ];

    protected $casts = [
        'useful_life_financial' => 'integer',
        'useful_life_tax' => 'integer',
        'residual_value_financial' => 'decimal:2',
        'residual_value_tax' => 'decimal:2',
        'depreciation_rate_tax' => 'decimal:4',
        'is_revaluation_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
