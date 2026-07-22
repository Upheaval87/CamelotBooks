<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoiceTemplateLine extends Model
{
    protected $fillable = [
        'rit_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'income_account_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoiceTemplate::class, 'rit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }
}
