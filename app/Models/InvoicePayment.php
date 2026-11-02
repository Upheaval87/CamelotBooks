<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * INVOICES MODULE 5.1 · invoice_payments — an immutable receipt.
 *
 * There is deliberately no update or delete path: a mistaken receipt is
 * corrected with a credit note, never by editing history (5.1 "NO update/delete
 * paths"). The model therefore omits the mutating helpers entirely and the
 * status derivation in 5.4 runs purely from the sum of these rows.
 */
class InvoicePayment extends Model
{
    use TenantScoped;

    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_CHEQUE = 'cheque';
    public const METHOD_SPLIT = 'split';

    /** 5.1 method enum. Order is the order the modal renders them in. */
    public const METHODS = [
        self::METHOD_BANK_TRANSFER,
        self::METHOD_MOBILE_MONEY,
        self::METHOD_CASH,
        self::METHOD_CARD,
        self::METHOD_CHEQUE,
        self::METHOD_SPLIT,
    ];

    protected $fillable = [
        'company_id',
        'invoice_id',
        'receipt_no',
        'paid_on',
        'method',
        'method_label',
        'amount',
        'allocation_note',
        'details',
        'splits',
        'recorded_by',
    ];

    protected $casts = [
        'paid_on' => 'date',
        'amount' => 'decimal:2',
        'details' => 'array',
        'splits' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function methodName(): string
    {
        return $this->method_label ?: str_replace('_', ' ', ucfirst((string) $this->method));
    }

    /**
     * 8.3: mobile-money needs a phone, cheque needs a bank + number. Card is the
     * only method that must NOT expose those, and instead asks for the name on
     * the card and the issuing bank.
     */
    public function detailValue(string $key): ?string
    {
        $value = $this->details[$key] ?? null;

        return $value === null ? null : (string) $value;
    }
}