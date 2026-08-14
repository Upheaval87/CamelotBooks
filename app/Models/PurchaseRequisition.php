<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'requisition_number',
        'date',
        'status',
        'priority',
        'required_by',
        'requested_by',
        'department',
        'supplier',
        'converted_to_po_id',
        'rejected_reason',
        'submitted_at',
        'converted_at',
        'reference',
        'memo',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'required_by' => 'date',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CONVERTED = 'converted';
    const STATUS_VOID = 'void';

    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_URGENT = 'urgent';

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_VOID => 'Void',
            default => ucfirst((string) $this->status),
        };
    }

    public function subtotal(): float
    {
        return round((float) $this->lines->sum('estimated_total'), 2);
    }

    public function estimatedTax(): float
    {
        return round($this->lines->sum(function ($line) {
            $rate = (float) ($line->product?->tax_rate ?? 0);
            return (float) $line->estimated_total * $rate / 100;
        }), 2);
    }

    public function grandTotal(): float
    {
        return round($this->subtotal() + $this->estimatedTax(), 2);
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

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_to_po_id');
    }

    public function purchaseRequisitionLines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
