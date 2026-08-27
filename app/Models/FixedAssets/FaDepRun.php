<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaDepRun extends Model
{
    use TenantScoped;

    protected $table = 'fa_dep_runs';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'company_id',
        'run_number',
        'period',
        'period_start',
        'period_end',
        'book_type',
        'asset_count',
        'total_depreciation',
        'journal_entry_id',
        'status',
        'notes',
        'run_by',
        'approved_by',
        'run_at',
        'approved_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'asset_count' => 'integer',
        'total_depreciation' => 'decimal:2',
        'run_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FaDepRunLine::class, 'run_id');
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'run_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
