<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionReversal extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'reversal_request_id', 'original_journal_entry_id',
        'reversal_journal_entry_id', 'reversal_number', 'reversal_date',
        'amount', 'created_by',
    ];

    protected $casts = [
        'reversal_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TransactionReversalRequest::class, 'reversal_request_id');
    }

    public function originalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'original_journal_entry_id');
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
