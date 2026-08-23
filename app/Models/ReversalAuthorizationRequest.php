<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversalAuthorizationRequest extends Model
{
    use TenantScoped;

    protected $table = 'reversal_authorization_requests';

    protected $fillable = [
        'company_id', 'reversal_request_id', 'approval_level',
        'assigned_to', 'status', 'comments',
        'approved_by', 'approved_date',
    ];

    protected $casts = [
        'approved_date' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TransactionReversalRequest::class, 'reversal_request_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'needs_clarification' => 'Needs Clarification',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'amber',
            'approved' => 'green',
            'rejected' => 'red',
            'needs_clarification' => 'amber',
            default => 'gray',
        };
    }
}
