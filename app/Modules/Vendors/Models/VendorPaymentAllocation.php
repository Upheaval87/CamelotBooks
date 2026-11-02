<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPaymentAllocation extends Model
{
    protected $table = 'vendor_payment_allocations';
    protected $guarded = [];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];
}
