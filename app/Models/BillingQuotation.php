<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform quotation for a branch request. Money figures are frozen at issue
 * time (see the matching tenant migration); `pricing_breakdown` records the
 * pricing inputs that produced them.
 */
class BillingQuotation extends Model
{
    use TenantScoped;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'branch_request_id',
        'quotation_number',
        'status',
        'unit_price',
        'quantity',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'currency_code',
        'pricing_breakdown',
        'bank_reference',
        'created_by_user_id',
        'valid_until',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'integer',
        'subtotal' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
        'pricing_breakdown' => 'array',
        'valid_until' => 'datetime',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branchRequest(): BelongsTo
    {
        return $this->belongsTo(BranchRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BranchPayment::class, 'billing_quotation_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucwords($this->status),
        };
    }
}
