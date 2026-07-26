<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemUomConversion extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'uom_name',
        'conversion_factor',
        'purchase_price',
        'sales_price',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
