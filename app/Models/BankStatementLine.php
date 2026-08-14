<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementLine extends Model
{
    use TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'company_id',
        'reconciliation_id',
        'bank_account_id',
        'transaction_date',
        'description',
        'reference',
        'amount',
        'balance',
        'is_matched',
        'status',
        'match_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_matched' => 'boolean',
    ];

    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_OUTSTANDING = 'outstanding';
    public const STATUS_PENDING = 'pending';
    public const STATUS_BANK_ONLY = 'bank_only';
    public const STATUS_BOOK_ONLY = 'book_only';
    public const STATUS_ADJUSTED = 'adjusted';
    public const STATUS_RECONCILED = 'reconciled';

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'reconciliation_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(ReconciliationMatch::class, 'match_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(ReconciliationAdjustment::class, 'bank_statement_line_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'] ?? now();
    }
}
