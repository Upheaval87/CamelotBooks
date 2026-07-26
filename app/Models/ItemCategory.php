<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'default_income_account_id',
        'default_cogs_account_id',
        'default_inventory_asset_account_id',
        'default_reorder_point',
        'default_base_uom',
        'is_active',
    ];

    protected $casts = [
        'default_reorder_point' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function defaultIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_income_account_id');
    }

    public function defaultCogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_cogs_account_id');
    }

    public function defaultInventoryAssetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_inventory_asset_account_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
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
