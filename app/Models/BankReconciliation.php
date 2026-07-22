<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'import_id',
        'statement_date',
        'statement_balance',
        'book_balance',
        'cleared_balance',
        'status',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'cleared_balance' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'reconciliation_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
