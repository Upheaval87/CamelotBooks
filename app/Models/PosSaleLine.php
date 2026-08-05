<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'transaction_uom',
        'transaction_qty',
        'conversion_factor',
        'quantity',
        'unit_price',
        'discount_amount',
        'discount_type',
        'tax_rate',
        'tax_amount',
        'line_total',
        'cost_of_goods',
    ];

    protected $casts = [
        'transaction_qty' => 'decimal:4',
        'conversion_factor' => 'decimal:4',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cost_of_goods' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
