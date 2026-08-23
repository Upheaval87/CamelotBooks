<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxReturnLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'return_id',
        'section',
        'label',
        'amount',
        'drill_query',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function taxReturn(): BelongsTo
    {
        return $this->belongsTo(TaxReturn::class, 'return_id');
    }
}
