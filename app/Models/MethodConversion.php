<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

/**
 * A cash→accrual accounting-method conversion for a company.
 *
 * Lives in the TENANT database (it references the tenant journal entry posted
 * via JournalPostingEngine). The authoritative company method flag lives on
 * the CENTRAL companies row; `method_conversions` records the conversion event
 * (cut-off date, journal, actor) for auditability.
 *
 * There is at most one active (non-`activated`) conversion per company.
 */
class MethodConversion extends Model
{
    use TenantScoped;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVATED = 'activated';

    protected $fillable = [
        'company_id',
        'from_method',
        'to_method',
        'cut_off_date',
        'treatment',
        'conversion_journal_id',
        'status',
        'created_by',
        'activated_at',
        'activated_by',
    ];

    protected $casts = [
        'from_method' => 'string',
        'to_method' => 'string',
        'cut_off_date' => 'date',
        'treatment' => 'string',
        'status' => 'string',
        'activated_at' => 'timestamp',
    ];

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function conversionJournal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'conversion_journal_id');
    }

    public function conversionJournalNumber(): ?string
    {
        return $this->conversionJournal?->journal_number;
    }

    public function isActivated(): bool
    {
        return $this->status === self::STATUS_ACTIVATED;
    }
}
