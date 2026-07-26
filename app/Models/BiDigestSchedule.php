<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiDigestSchedule extends Model
{
    protected $fillable = [
        'company_id',
        'frequency',
        'recipients',
        'last_sent_at',
        'is_active',
    ];

    protected $casts = [
        'recipients'   => 'array',
        'is_active'    => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    const FREQUENCY_DAILY   = 'daily';
    const FREQUENCY_WEEKLY  = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
}
