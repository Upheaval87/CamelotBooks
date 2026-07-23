<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'employee_number',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'position',
        'department',
        'hire_date',
        'termination_date',
        'employment_status',
        'tax_id',
        'national_id',
        'pension_member_number',
        'pension_scheme_id',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_branch_code',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function currentSalaryStructure(): HasOne
    {
        return $this->hasOne(EmployeeSalaryStructure::class)
            ->where('is_current', true);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeePayment::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('employment_status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return implode(' ', $parts);
    }
}
