<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayeTableBand extends Model
{
    protected $fillable = [
        'paye_table_id',
        'threshold',
        'upper_limit',
        'rate',
        'sort_order',
    ];

    protected $casts = [
        'threshold' => 'decimal:2',
        'upper_limit' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    public function payeTable(): BelongsTo
    {
        return $this->belongsTo(PayeTable::class);
    }
}
