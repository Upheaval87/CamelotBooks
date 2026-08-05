<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'transaction_uom',
        'transaction_qty',
        'conversion_factor',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'expense_account_id',
        'cost_center_id',
        'quantity_received',
        'quantity_billed',
    ];

    protected $casts = [
        'transaction_qty' => 'decimal:4',
        'conversion_factor' => 'decimal:4',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'quantity_billed' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'purchase_order_line_id');
    }
}
