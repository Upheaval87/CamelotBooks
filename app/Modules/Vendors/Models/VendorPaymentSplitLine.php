<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPaymentSplitLine extends Model
{
    protected $table = 'vendor_payment_split_lines';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'change_given' => 'decimal:2',
    ];
}
