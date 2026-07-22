<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'bank_account_id',
        'transaction_date',
        'description',
        'reference',
        'amount',
        'balance',
        'is_matched',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_matched' => 'boolean',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'bank_statement_line_id');
    }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'] ?? now();
    }
}
