<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_documents';
    protected $guarded = [];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
