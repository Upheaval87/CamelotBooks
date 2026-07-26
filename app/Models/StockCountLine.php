<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountLine extends Model
{
    protected $fillable = [
        'stock_count_id',
        'product_id',
        'expected_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'variance_cost',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:4',
        'counted_quantity' => 'decimal:4',
        'variance_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'variance_cost' => 'decimal:2',
    ];

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
