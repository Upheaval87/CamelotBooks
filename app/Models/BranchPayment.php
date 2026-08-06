<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Offline payment recorded against a billing quotation (see the matching
 * tenant migration). Payments are never auto-confirmed: `status` stays
 * `pending` until a billing/accounting staff member confirms it, which is the
 * only action that raises the company's branch_limit.
 */
class BranchPayment extends Model
{
    use TenantScoped;

    protected $table = 'payments';

    public const MODE_BANK_TRANSFER = 'bank_transfer';
    public const MODE_CHEQUE = 'cheque';
    public const MODE_CASH = 'cash';

    public const MODES = [self::MODE_BANK_TRANSFER, self::MODE_CHEQUE, self::MODE_CASH];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'billing_quotation_id',
        'payment_mode',
        'reference_no',
        'bank_name',
        'amount',
        'notes',
        'status',
        'recorded_by_user_id',
        'confirmed_by_user_id',
        'paid_at',
        'confirmed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(BillingQuotation::class, 'billing_quotation_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function modeLabel(): string
    {
        return match ($this->payment_mode) {
            self::MODE_BANK_TRANSFER => 'Bank Transfer',
            self::MODE_CHEQUE => 'Cheque',
            self::MODE_CASH => 'Cash',
            default => ucwords(str_replace('_', ' ', $this->payment_mode)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_REJECTED => 'Rejected',
            default => ucwords($this->status),
        };
    }
}
