<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetImpairment extends Model
{
    protected $fillable = [
        'asset_id',
        'company_id',
        'impairment_date',
        'recoverable_amount',
        'previous_nbv',
        'impairment_amount',
        'is_reversal',
        'reversed_impairment_id',
        'journal_entry_id',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'impairment_date' => 'date',
        'recoverable_amount' => 'decimal:2',
        'previous_nbv' => 'decimal:2',
        'impairment_amount' => 'decimal:2',
        'is_reversal' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reversedImpairment(): BelongsTo
    {
        return $this->belongsTo(AssetImpairment::class, 'reversed_impairment_id');
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
