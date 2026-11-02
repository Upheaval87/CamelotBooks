<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEvaluation extends Model
{
    use SoftDeletes;

    protected $table = 'vendor_evaluations';
    protected $guarded = [];

    protected $casts = [
        'quality_score' => 'integer',
        'delivery_score' => 'integer',
        'price_score' => 'integer',
        'communication_score' => 'integer',
        'compliance_score' => 'integer',
        'weighted_score' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
