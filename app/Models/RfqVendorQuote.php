<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqVendorQuote extends Model
{
    use TenantScoped;

    protected $fillable = [
        'rfq_line_id',
        'vendor_id',
        'quoted_unit_price',
        'lead_time_days',
        'notes',
    ];

    protected $casts = [
        'quoted_unit_price' => 'decimal:4',
    ];

    public function rfqLine(): BelongsTo
    {
        return $this->belongsTo(RfqLine::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
