<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $fillable = [
        'asset_id',
        'company_id',
        'disposal_date',
        'disposal_method',
        'proceeds_amount',
        'proceeds_account_id',
        'gain_loss_amount',
        'journal_entry_id',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'proceeds_amount' => 'decimal:2',
        'gain_loss_amount' => 'decimal:2',
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
