<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReceiptPayment extends Model
{
    protected $fillable = [
        'sales_receipt_id',
        'payment_method_id',
        'amount',
        'cash_tendered',
        'change_given',
        'reference_number',
        'account_name',
        'institution',
        'bank_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'change_given' => 'decimal:2',
    ];

    public function salesReceipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PosPaymentMethod::class, 'payment_method_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }
}
