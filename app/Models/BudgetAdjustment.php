<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BudgetAdjustment extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'budget_id', 'budget_line_id', 'code', 'type',
        'from_line_id', 'to_line_id', 'amount', 'reason', 'status',
        'requested_by', 'approved_by', 'approved_at', 'approval_comment',
        'original_amount',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'original_amount' => 'decimal:2',
        'approved_at'     => 'datetime',
    ];

    public const TYPES = [
        'increase'  => 'Increase',
        'reduce'    => 'Reduce',
        'transfer'  => 'Transfer',
    ];

    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public function budget(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function budgetLine(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function fromLine(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'from_line_id');
    }

    public function toLine(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'to_line_id');
    }

    public function requestedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'  => 'pending',
            'approved' => 'active',
            'rejected' => 'over',
            default    => 'steel',
        };
    }
}
