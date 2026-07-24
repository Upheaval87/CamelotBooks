<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashierSession extends Model
{
    protected $fillable = [
        'company_id',
        'terminal_id',
        'user_id',
        'opening_float',
        'status',
        'closed_at',
        'actual_cash_count',
        'expected_cash',
        'variance',
    ];

    protected $casts = [
        'opening_float' => 'decimal:2',
        'actual_cash_count' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'terminal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
