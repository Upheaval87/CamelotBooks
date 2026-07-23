<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'company_code',
        'tax_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'base_currency',
        'fiscal_year_start_month',
        'logo',
        'is_active',
        'allow_negative_stock',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'fiscal_year_start_month' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function accountingPeriods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function approvalSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ApprovalSetting::class);
    }

    public function recurringJournalTemplates(): HasMany
    {
        return $this->hasMany(RecurringJournalTemplate::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function inventoryStock(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function inventoryTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class);
    }
}
