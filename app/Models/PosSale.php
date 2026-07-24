<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $fillable = [
        'company_id',
        'terminal_id',
        'cashier_session_id',
        'customer_id',
        'branch_id',
        'cost_center_id',
        'sale_number',
        'reference',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'status',
        'is_on_account',
        'synced_from_offline',
        'offline_transaction_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'is_on_account' => 'boolean',
        'synced_from_offline' => 'boolean',
    ];

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'terminal_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }
}
