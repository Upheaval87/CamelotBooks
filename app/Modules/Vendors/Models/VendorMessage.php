<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorMessage extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_messages';
    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
