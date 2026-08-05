<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReturnLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'pos_return_id',
        'pos_sale_line_id',
        'product_id',
        'quantity_returned',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'line_total',
        'cost_of_goods',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cost_of_goods' => 'decimal:2',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class, 'pos_return_id');
    }

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(PosSaleLine::class, 'pos_sale_line_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
