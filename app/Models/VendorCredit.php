<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorCredit extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'vendor_id',
        'credit_note_number',
        'credit_note_date',
        'reference',
        'memo',
        'status',
        'amount',
        'amount_applied',
        'amount_refunded',
        'bill_id',
        'journal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'amount' => 'decimal:2',
        'amount_applied' => 'decimal:2',
        'amount_refunded' => 'decimal:2',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_APPLIED = 'applied';
    const STATUS_VOID = 'void';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorCreditLine::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }
}
