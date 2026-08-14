<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'bank_account_id',
        'journal_entry_id',
        'type',
        'source_type',
        'source_id',
        'date',
        'description',
        'reference',
        'amount',
        'foreign_amount',
        'foreign_currency',
        'exchange_rate',
        'linked_transaction_id',
        'is_reconciled',
        'reconciled_at',
        'bank_reconciliation_id',
        'reconciliation_status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    public const RECON_STATUS_UNMATCHED = 'unmatched';
    public const RECON_STATUS_MATCHED = 'matched';
    public const RECON_STATUS_OUTSTANDING = 'outstanding';
    public const RECON_STATUS_PENDING = 'pending';
    public const RECON_STATUS_BOOK_ONLY = 'book_only';
    public const RECON_STATUS_ADJUSTED = 'adjusted';
    public const RECON_STATUS_RECONCILED = 'reconciled';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'bank_reconciliation_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function linkedTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'linked_transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeForBankAccount($query, int $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }
}
