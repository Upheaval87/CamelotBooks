<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipDelivery extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'status',
        'email_address',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeNotSent($query)
    {
        return $query->where('status', 'not_sent');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
}
