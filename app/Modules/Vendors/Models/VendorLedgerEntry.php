<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorLedgerEntry extends Model
{
    protected $table = 'vendor_ledger_entries';
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
