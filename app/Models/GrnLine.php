<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'goods_received_note_id',
        'purchase_order_line_id',
        'product_id',
        'transaction_uom',
        'transaction_qty',
        'conversion_factor',
        'description',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'expense_account_id',
        'cost_center_id',
    ];

    protected $casts = [
        'transaction_qty' => 'decimal:4',
        'conversion_factor' => 'decimal:4',
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
    ];

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
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
}
