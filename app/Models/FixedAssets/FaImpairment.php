<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaImpairment extends Model
{
    use TenantScoped;

    protected $table = 'fa_impairments';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'company_id',
        'asset_id',
        'impairment_date',
        'carrying_value',
        'recoverable_amount',
        'impairment_loss',
        'is_reversal',
        'reversal_amount',
        'reason',
        'journal_entry_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'impairment_date' => 'date',
        'carrying_value' => 'decimal:2',
        'recoverable_amount' => 'decimal:2',
        'impairment_loss' => 'decimal:2',
        'is_reversal' => 'boolean',
        'reversal_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReversals(Builder $query): Builder
    {
        return $query->where('is_reversal', true);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isReversal(): bool
    {
        return $this->is_reversal;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_reversal ? 'Reversal' : ucfirst($this->status);
    }
}
