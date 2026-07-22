<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPaymentAllocation extends Model
{
    protected $fillable = [
        'vendor_payment_id',
        'bill_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
