<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationScheduleEntry extends Model
{
    protected $fillable = [
        'asset_id',
        'asset_depreciation_book_id',
        'depreciation_run_id',
        'period_number',
        'period_start_date',
        'period_end_date',
        'opening_nbv',
        'depreciation_charge',
        'accumulated_depreciation',
        'closing_nbv',
        'units_used',
        'is_posted',
        'posted_at',
        'journal_entry_id',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'opening_nbv' => 'decimal:2',
        'depreciation_charge' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'closing_nbv' => 'decimal:2',
        'units_used' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'timestamp',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function depreciationBook(): BelongsTo
    {
        return $this->belongsTo(AssetDepreciationBook::class, 'asset_depreciation_book_id');
    }

    public function depreciationRun(): BelongsTo
    {
        return $this->belongsTo(DepreciationRun::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
