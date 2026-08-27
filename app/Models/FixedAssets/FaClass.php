<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaClass extends Model
{
    use TenantScoped;

    protected $table = 'fa_classes';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'default_dep_method',
        'default_useful_life',
        'default_residual_pct',
        'is_active',
    ];

    protected $casts = [
        'default_useful_life' => 'integer',
        'default_residual_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FaAsset::class, 'class_id');
    }
}
