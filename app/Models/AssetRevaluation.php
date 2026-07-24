<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRevaluation extends Model
{
    protected $fillable = [
        'asset_id',
        'company_id',
        'revaluation_date',
        'previous_nbv',
        'fair_value',
        'surplus_amount',
        'existing_surplus_offset',
        'journal_entry_id',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'previous_nbv' => 'decimal:2',
        'fair_value' => 'decimal:2',
        'surplus_amount' => 'decimal:2',
        'existing_surplus_offset' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

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
}
