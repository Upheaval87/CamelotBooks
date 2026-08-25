<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PosReturnable extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'product_id',
        'customer_id',
        'branch_id',
        'quantity',
        'bottle_count',
        'credit_amount',
        'value_each',
        'intake_number',
        'brr_number',
        'expiry_date',
        'status',
        'redeemed_qty',
        'redeemed_at',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'redeemed_at' => 'datetime',
        'credit_amount' => 'decimal:2',
        'value_each' => 'decimal:2',
        'quantity' => 'integer',
        'bottle_count' => 'integer',
        'redeemed_qty' => 'integer',
    ];

    protected $hidden = [
        'journal_entry_id',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_REDEEMED = 'partially_redeemed';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_VOIDED = 'voided';

    // Relations

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnredeemed($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIALLY_REDEEMED]);
    }

    public function scopeForBranch($query, ?int $branchId)
    {
        if ($branchId === null) {
            return $query;
        }
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()->toDateString());
    }

    // Accessors

    protected function remainingCredit(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->credit_amount - ($this->redeemed_qty * (float) $this->value_each), 2),
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expiry_date !== null && $this->expiry_date->isPast(),
        );
    }

    protected function isHeadOffice(): Attribute
    {
        return Attribute::make(
            get: fn () => str_contains($this->branch?->name ?? '', 'Head Office'),
        );
    }

    protected function fullyRedeemed(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->redeemed_qty >= $this->quantity,
        );
    }

    // Helpers

    public function isVoidable(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->redeemed_qty === 0;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'pending',
            self::STATUS_PARTIALLY_REDEEMED => 'pending',
            self::STATUS_REDEEMED => 'active',
            self::STATUS_EXPIRED => 'muted',
            self::STATUS_VOIDED => 'danger',
            default => 'muted',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PARTIALLY_REDEEMED => 'Partial',
            self::STATUS_REDEEMED => 'Redeemed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_VOIDED => 'Voided',
            default => ucfirst($this->status),
        };
    }
}
