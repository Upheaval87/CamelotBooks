<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringBillTemplateLine extends Model
{
    protected $fillable = [
        'rbt_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'expense_account_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringBillTemplate::class, 'rbt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }
}
