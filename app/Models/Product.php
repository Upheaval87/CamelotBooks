<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'sku',
        'type',
        'sales_price',
        'purchase_price',
        'income_account_id',
        'expense_account_id',
        'tax_rate',
        'is_taxable',
        'is_active',
    ];

    protected $casts = [
        'sales_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function billLines(): HasMany
    {
        return $this->hasMany(BillLine::class);
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
