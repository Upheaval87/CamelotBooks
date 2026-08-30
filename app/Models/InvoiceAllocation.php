<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAllocation extends Model
{
    use TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'receipt_id',
        'payment_id',
        'applied_amount',
    ];

    protected $casts = [
        'applied_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'receipt_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalesReceiptPayment::class, 'payment_id');
    }
}
