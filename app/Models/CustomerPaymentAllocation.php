<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentAllocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_payment_id',
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'] ?? now();
    }
}
