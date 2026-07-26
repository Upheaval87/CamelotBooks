<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandedCostComponent extends Model
{
    protected $fillable = [
        'voucher_id',
        'component_type',
        'description',
        'amount',
        'payee_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(LandedCostVoucher::class, 'voucher_id');
    }

    public function payeeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payee_account_id');
    }
}
