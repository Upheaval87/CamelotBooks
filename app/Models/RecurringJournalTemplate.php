<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringJournalTemplate extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'name', 'reference', 'branch_id', 'cost_center_id',
        'memo', 'description', 'journal_type', 'currency', 'frequency',
        'day_of_month', 'day_of_week', 'start_date', 'end_date',
        'next_run_date', 'occurrences', 'generation_mode', 'email_notification',
        'auto_post', 'is_active', 'status', 'total_amount',
        'last_generated_at', 'failed_count', 'generated_count', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'last_generated_at' => 'datetime',
        'auto_post' => 'boolean',
        'is_active' => 'boolean',
        'day_of_month' => 'integer',
        'day_of_week' => 'integer',
        'occurrences' => 'integer',
        'total_amount' => 'decimal:2',
        'failed_count' => 'integer',
        'generated_count' => 'integer',
    ];

    const FREQ_DAILY = 'daily';
    const FREQ_WEEKLY = 'weekly';
    const FREQ_BIWEEKLY = 'biweekly';
    const FREQ_MONTHLY = 'monthly';
    const FREQ_QUARTERLY = 'quarterly';
    const FREQ_SEMI_ANNUALLY = 'semi_annually';
    const FREQ_YEARLY = 'yearly';
    const FREQ_CUSTOM = 'custom';

    const TYPE_STANDARD = 'standard';
    const TYPE_ACCRUAL = 'accrual';
    const TYPE_DEPRECIATION = 'depreciation';
    const TYPE_PREPAYMENT = 'prepayment';
    const TYPE_ADJUSTMENT = 'adjustment';

    const MODE_AUTO_POST = 'auto_post';
    const MODE_APPROVAL_FIRST = 'approval_first';
    const MODE_DRAFT_ONLY = 'draft_only';

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_EXPIRED = 'expired';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateLines(): HasMany
    {
        return $this->hasMany(RecurringJournalTemplateLine::class, 'rjt_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'recurring_template_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(RecurringJournalRun::class, 'recurring_journal_template_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(RecurringJournalHistory::class, 'recurring_journal_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeDue($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('next_run_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->journal_type, [self::TYPE_STANDARD, self::TYPE_ACCRUAL, self::TYPE_PREPAYMENT]);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'b-act',
            self::STATUS_PAUSED => 'b-pend',
            self::STATUS_EXPIRED => 'b-inact',
            default => 'b-draft',
        };
    }

    public function typeChipClass(): string
    {
        return match ($this->journal_type) {
            self::TYPE_ACCRUAL => 'tchip-amber',
            self::TYPE_DEPRECIATION => 'tchip-steel',
            self::TYPE_PREPAYMENT => 'tchip-teal',
            self::TYPE_ADJUSTMENT => 'tchip-steel',
            default => '',
        };
    }
}
