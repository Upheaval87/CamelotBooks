<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];
}
