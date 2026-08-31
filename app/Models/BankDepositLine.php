<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDepositLine extends Model
{
    use TenantScoped;

    public const SOURCE_TYPE_RECEIPT = 'receipt';

    protected $fillable = [
        'deposit_id',
        'source_type',
        'source_id',
        'sales_receipt_id',
        'reference',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(BankDeposit::class, 'deposit_id');
    }

    public function sourceLine(): BelongsTo
    {
        // source_id = the undeposited 1050-debit journal_entry_lines.id
        return $this->belongsTo(JournalEntryLine::class, 'source_id');
    }

    public function salesReceipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'sales_receipt_id');
    }
}
