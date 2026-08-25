<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPriceListItem extends Model
{
    use TenantScoped;

    protected $fillable = [
        'price_list_id',
        'product_id',
        'unit_price',
        'min_qty',
        'max_qty',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PosPriceList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
