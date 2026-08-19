<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalRun extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'recurring_journal_template_id', 'journal_entry_id',
        'run_date', 'reference', 'status', 'total_debit', 'total_credit',
        'failure_reason', 'retry_count', 'is_test', 'created_by',
    ];

    protected $casts = [
        'run_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_test' => 'boolean',
        'retry_count' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_POSTED = 'posted';
    const STATUS_REVERSED = 'reversed';
    const STATUS_FAILED = 'failed';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringJournalTemplate::class, 'recurring_journal_template_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_POSTED => 'b-act',
            self::STATUS_PENDING_APPROVAL => 'b-pend',
            self::STATUS_DRAFT => 'b-draft',
            self::STATUS_REVERSED => 'b-inact',
            self::STATUS_FAILED => 'b-out',
            default => 'b-draft',
        };
    }
}
