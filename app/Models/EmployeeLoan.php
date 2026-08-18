<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'employee_id',
        'loan_number',
        'principal_amount',
        'outstanding_balance',
        'monthly_deduction',
        'interest_rate',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'principal_amount'     => 'decimal:2',
        'outstanding_balance'  => 'decimal:2',
        'monthly_deduction'    => 'decimal:2',
        'interest_rate'        => 'decimal:2',
        'start_date'           => 'date',
        'end_date'             => 'date',
    ];

    public const STATUSES = ['active', 'paid_off', 'written_off', 'defaulted'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function isFullyPaid(): bool
    {
        return $this->outstanding_balance <= 0;
    }
}
