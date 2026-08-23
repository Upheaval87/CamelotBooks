<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemReturnable extends Model
{
    use TenantScoped;

    protected $table = 'items_returnable';

    protected $fillable = [
        'company_id',
        'item_id',
        'container_type',
        'deposit_value',
        'deposit_tax_handling',
        'return_window_days',
        'linked_empty_item_id',
        'linked_filled_item_id',
        'required_return',
        'container_stock_account_id',
        'container_stock_tracking',
        'allow_cash_refund',
    ];

    protected $casts = [
        'deposit_value' => 'decimal:2',
        'return_window_days' => 'integer',
        'container_stock_tracking' => 'boolean',
        'allow_cash_refund' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function linkedEmptyItem(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'linked_empty_item_id');
    }

    public function linkedFilledItem(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'linked_filled_item_id');
    }

    public function containerStockAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'container_stock_account_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
