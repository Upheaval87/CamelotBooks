<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayslipSetting extends Model
{
    use TenantScoped;

    protected $table = 'employee_payslip_settings';

    protected $fillable = [
        'company_id',
        'employee_id',
        'email_delivery',
        'portal_access',
        'custom_email',
        'access_pin',
    ];

    protected $casts = [
        'email_delivery' => 'boolean',
        'portal_access'  => 'boolean',
    ];

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
}
