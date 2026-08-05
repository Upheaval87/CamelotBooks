<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepreciationRun extends Model
{
    use TenantScoped;

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';

    protected $fillable = [
        'company_id',
        'run_number',
        'period',
        'period_start_date',
        'period_end_date',
        'status',
        'total_depreciation_amount',
        'assets_processed',
        'assets_skipped',
        'skip_reasons',
        'journal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'total_depreciation_amount' => 'decimal:2',
        'assets_processed' => 'integer',
        'assets_skipped' => 'integer',
        'skip_reasons' => 'array',
        'posted_at' => 'timestamp',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(DepreciationScheduleEntry::class);
    }
}
