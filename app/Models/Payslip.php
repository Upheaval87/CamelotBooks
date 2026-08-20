<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'payslip_number',
        'status',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'earnings',
        'deductions',
        'employer_contributions',
        'ytd_totals',
        'pdf_path',
        'generated_at',
        'finalized_at',
    ];

    protected $casts = [
        'gross_pay'               => 'decimal:2',
        'total_deductions'        => 'decimal:2',
        'net_pay'                 => 'decimal:2',
        'earnings'                => 'array',
        'deductions'              => 'array',
        'employer_contributions'  => 'array',
        'ytd_totals'              => 'array',
        'generated_at'            => 'datetime',
        'finalized_at'            => 'datetime',
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

    public function distributions()
    {
        return $this->hasMany(PayslipDistribution::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(PayslipAuditLog::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['finalized', 'sent', 'viewed']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'     => 'Draft',
            'finalized' => 'Finalized',
            'sent'      => 'Sent',
            'viewed'    => 'Viewed',
            default     => ucfirst($this->status),
        };
    }
}
