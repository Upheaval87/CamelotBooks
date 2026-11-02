<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'amount',
        'tax_amount',
        'line_total',
        'income_account_id',
        'cost_center_id',
        // ── full-module columns (2026_12_08_000001) ──
        'position',
        'item_code',
        'item_name',
        'qty_fulfilled',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'position' => 'integer',
        'qty_fulfilled' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function fulfilments(): HasMany
    {
        return $this->hasMany(OrderFulfilment::class);
    }

    /** R9 · Description is OPTIONAL; a line is complete without it. */
    public function label(): string
    {
        return trim((string) ($this->item_name ?: $this->description));
    }

    /** R12 · What the fulfil modal caps its input at. */
    public function remaining(): float
    {
        return round(max((float) $this->quantity - (float) ($this->qty_fulfilled ?? 0), 0), 2);
    }

    /**
     * R9 · A complete line carries an item code, an item name, a quantity above
     * zero, a unit price above zero and a computed amount above zero.
     */
    public function isComplete(): bool
    {
        return filled($this->item_code)
            && filled($this->item_name)
            && (float) $this->quantity > 0
            && (float) $this->unit_price > 0
            && (float) $this->amount > 0;
    }
}
