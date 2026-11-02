<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;

class VendorBillItem extends Model
{
    protected $table = 'vendor_bill_items';
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];
}
