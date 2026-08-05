<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCostLayer extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'product_id',
        'branch_id',
        'cost_center_id',
        'quantity_remaining',
        'unit_cost',
        'source_type',
        'source_id',
        'date',
    ];

    protected $casts = [
        'quantity_remaining' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity_remaining', '>', 0);
    }
}
