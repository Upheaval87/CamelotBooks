<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorNote extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_notes';
    protected $guarded = [];

    protected $casts = [
        'reminder_date' => 'date',
        'pinned' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
