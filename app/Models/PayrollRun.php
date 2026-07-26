<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'company_id',
        'run_number',
        'period_label',
        'pay_date',
        'period_start',
        'period_end',
        'status',
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
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'total_gross' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'total_pension_ee' => 'decimal:2',
        'total_pension_er' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net_pay' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_CALCULATED = 'calculated';
    const STATUS_APPROVED = 'approved';
    const STATUS_POSTED = 'posted';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_FULLY_PAID = 'fully_paid';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function payeTable(): BelongsTo
    {
        return $this->belongsTo(PayeTable::class);
    }

    public function pensionScheme(): BelongsTo
    {
        return $this->belongsTo(PensionScheme::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeePayment::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PayslipDelivery::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
