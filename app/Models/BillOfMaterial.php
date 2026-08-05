<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'assembly_product_id',
        'bom_number',
        'name',
        'estimated_cost',
        'is_active',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assemblyProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'assembly_product_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class, 'bom_id');
    }

    public function assemblyBuilds(): HasMany
    {
        return $this->hasMany(AssemblyBuild::class, 'bom_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
