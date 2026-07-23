<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'journal_number',
        'date',
        'reference',
        'memo',
        'status',
        'is_adjusting_entry',
        'source_module',
        'linked_entry_id',
        'recurring_template_id',
        'created_by',
        'posted_by',
        'posted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'is_adjusting_entry' => 'boolean',
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_POSTED = 'posted';
    const STATUS_REVERSED = 'reversed';

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (JournalEntry $entry) {
            $originalStatus = $entry->getOriginal('status');

            if (! in_array($originalStatus, [self::STATUS_POSTED, self::STATUS_REVERSED])) {
                return;
            }

            if ($originalStatus === self::STATUS_POSTED) {
                $dirty = $entry->getDirty();
                if (isset($dirty['status']) && $dirty['status'] === self::STATUS_REVERSED) {
                    return;
                }
            }

            throw new \BadMethodCallException(
                ucfirst($originalStatus) . ' journal entries are immutable and cannot be modified.'
            );
        });

        static::deleting(function (JournalEntry $entry) {
            if (in_array($entry->status, [self::STATUS_POSTED, self::STATUS_REVERSED])) {
                throw new \BadMethodCallException('Posted or reversed journal entries are immutable and cannot be deleted.');
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function linkedEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'linked_entry_id');
    }

    public function reversingEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'linked_entry_id');
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringJournalTemplate::class, 'recurring_template_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AccountAuditLog::class, 'journalable');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
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

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeReversed($query)
    {
        return $query->where('status', self::STATUS_REVERSED);
    }

    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeForBranch($query, ?int $branchId)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
