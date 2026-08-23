<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionReversalRequest extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'reference_number', 'journal_entry_id',
        'original_transaction_type', 'original_transaction_id',
        'requested_by', 'request_date', 'reversal_date',
        'reversal_method', 'partial_amount', 'reason', 'status',
        'approved_by', 'approved_date', 'rejection_reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'reversal_date' => 'date',
        'approved_date' => 'datetime',
        'partial_amount' => 'decimal:2',
    ];

    public const STATUS_PENDING = 'pending_authorization';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLARIFICATION = 'needs_clarification';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REVERSED = 'reversed';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending Authorization',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_CLARIFICATION => 'Needs Clarification',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_REVERSED => 'Reversed',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(TransactionReversal::class, 'reversal_request_id');
    }

    public function authorizationRequests(): HasMany
    {
        return $this->hasMany(ReversalAuthorizationRequest::class, 'reversal_request_id');
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ReversalApprovalHistory::class, 'reversal_request_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'amber',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CLARIFICATION => 'amber',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REVERSED => 'gray',
            default => 'gray',
        };
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
