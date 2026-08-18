<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class Employee extends Model
{
    use TenantScoped;

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
        'payslip_password',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function getPayslipPasswordDecryptedAttribute(): ?string
    {
        if (is_null($this->attributes['payslip_password'])) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['payslip_password']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function setPayslipPasswordValueAttribute(?string $value): void
    {
        $this->attributes['payslip_password'] = $value ? Crypt::encryptString($value) : null;
    }

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

    public function deliveries(): HasMany
    {
        return $this->hasMany(PayslipDelivery::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
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
