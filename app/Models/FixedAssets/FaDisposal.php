<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaDisposal extends Model
{
    use TenantScoped;

    protected $table = 'fa_disposals';

    public const METHOD_SALE = 'sale';
    public const METHOD_SCRAP = 'scrap';
    public const METHOD_DONATION = 'donation';
    public const METHOD_DESTROYED = 'destroyed';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'company_id',
        'asset_id',
        'disposal_date',
        'disposal_method',
        'proceeds_amount',
        'disposal_cost',
        'net_proceeds',
        'cost_acquisition',
        'accum_depreciation',
        'accum_impairment',
        'net_book_value',
        'gain_loss',
        'reason',
        'journal_entry_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'proceeds_amount' => 'decimal:2',
        'disposal_cost' => 'decimal:2',
        'net_proceeds' => 'decimal:2',
        'cost_acquisition' => 'decimal:2',
        'accum_depreciation' => 'decimal:2',
        'accum_impairment' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'gain_loss' => 'decimal:2',
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

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isGain(): bool
    {
        return (float) $this->gain_loss > 0;
    }

    public function isLoss(): bool
    {
        return (float) $this->gain_loss < 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getMethodLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->disposal_method));
    }
}
