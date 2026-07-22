<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'display_name',
        'email',
        'phone',
        'billing_address',
        'remit_to_address',
        'currency',
        'payment_terms',
        'payment_terms_days',
        'opening_balance',
        'opening_balance_date',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'opening_balance_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getBalanceDueAttribute(): float
    {
        $openBills = $this->bills()
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->get();

        $totalAmount = (float) $openBills->sum('amount');
        $totalPaid = (float) $openBills->sum('amount_paid');

        return $totalAmount - $totalPaid;
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
