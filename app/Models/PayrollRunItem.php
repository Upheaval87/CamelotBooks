<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunItem extends Model
{
    use TenantScoped;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_pay',
        'total_allowances',
        'gross_pay',
        'paye',
        'pension_ee',
        'total_deductions',
        'net_pay',
        'pension_er',
        'employer_pension_expense',
        'payslip_data',
    ];

    protected $casts = [
        'basic_pay'                   => 'decimal:2',
        'total_allowances'            => 'decimal:2',
        'gross_pay'                   => 'decimal:2',
        'paye'                        => 'decimal:2',
        'pension_ee'                  => 'decimal:2',
        'total_deductions'            => 'decimal:2',
        'net_pay'                     => 'decimal:2',
        'pension_er'                  => 'decimal:2',
        'employer_pension_expense'    => 'decimal:2',
        'payslip_data'               => 'array',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->whereHas('payrollRun', fn($q) => $q->where('company_id', $companyId));
    }
}
