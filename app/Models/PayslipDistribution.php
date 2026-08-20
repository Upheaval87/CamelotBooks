<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipDistribution extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'payslip_id',
        'employee_id',
        'channel',
        'status',
        'email_address',
        'error_message',
        'retry_count',
        'sent_at',
        'delivered_at',
        'last_retry_at',
    ];

    protected $casts = [
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Pending',
            'sent'      => 'Sent',
            'delivered' => 'Delivered',
            'failed'    => 'Failed',
            default     => ucfirst($this->status),
        };
    }
}
