<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SALES ORDERS MODULE §5.1 — one row per line per delivery batch.
 *
 * This is the partial-delivery audit trail: every fulfilment writes one row per
 * line, tagged with the delivery-note number and the receiver, so the ordered
 * vs fulfilled history survives even after the order is closed out.
 */
class OrderFulfilment extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'sales_order_id',
        'sales_order_line_id',
        'quantity',
        'fulfil_date',
        'delivery_note_no',
        'received_by',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'fulfil_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class, 'sales_order_line_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}