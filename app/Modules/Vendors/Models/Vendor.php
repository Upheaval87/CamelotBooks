<?php

namespace App\Modules\Vendors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(VendorMessage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(VendorNote::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(VendorEvaluation::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(VendorLedgerEntry::class);
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->display_name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        return $initials;
    }
}
