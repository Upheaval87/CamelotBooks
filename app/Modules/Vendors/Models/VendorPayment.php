<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayment extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_payments';
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function splitLines(): HasMany
    {
        return $this->hasMany(VendorPaymentSplitLine::class, 'payment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VendorPaymentAllocation::class, 'payment_id');
    }
}
