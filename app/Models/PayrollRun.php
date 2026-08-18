<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'run_number',
        'period_label',
        'pay_date',
        'period_start',
        'period_end',
        'status',
        'approved_at',
        'approved_by',
        'total_gross',
        'total_paye',
        'total_pension_ee',
        'total_pension_er',
        'total_deductions',
        'total_net_pay',
        'journal_entry_id',
        'paye_table_id',
        'pension_scheme_id',
        'created_by',
    ];

    protected $casts = [
        'pay_date'          => 'date',
        'period_start'      => 'date',
        'period_end'        => 'date',
        'approved_at'       => 'datetime',
        'total_gross'       => 'decimal:2',
        'total_paye'        => 'decimal:2',
        'total_pension_ee'  => 'decimal:2',
        'total_pension_er'  => 'decimal:2',
        'total_deductions'  => 'decimal:2',
        'total_net_pay'     => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function payeTable(): BelongsTo
    {
        return $this->belongsTo(PayeTable::class);
    }

    public function pensionScheme(): BelongsTo
    {
        return $this->belongsTo(PensionScheme::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'calculated', 'pending_approval']);
    }
}
