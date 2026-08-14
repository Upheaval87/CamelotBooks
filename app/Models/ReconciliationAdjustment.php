<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationAdjustment extends Model
{
    use TenantScoped;

    protected $table = 'bank_reconciliation_adjustments';

    public const TYPE_BANK_FEES = 'bank_fees';
    public const TYPE_INTEREST_EARNED = 'interest_earned';
    public const TYPE_BOOK_ERROR = 'book_error';
    public const TYPE_BANK_ERROR = 'bank_error';
    public const TYPE_FOREIGN_EXCHANGE = 'foreign_exchange';
    public const TYPE_UNCLEARED_CHEQUE = 'uncleared_cheque';
    public const TYPE_DEPOSIT_IN_TRANSIT = 'deposit_in_transit';
    public const TYPE_MISSING_TRANSACTION = 'missing_transaction';
    public const TYPE_DUPLICATE = 'duplicate';
    public const TYPE_REVERSAL = 'reversal';

    public const SIDE_BOOK = 'book';
    public const SIDE_BANK = 'bank';

    public const SIGN_ADD = 'add';
    public const SIGN_SUBTRACT = 'subtract';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';

    public const TYPES = [
        self::TYPE_BANK_FEES,
        self::TYPE_INTEREST_EARNED,
        self::TYPE_BOOK_ERROR,
        self::TYPE_BANK_ERROR,
        self::TYPE_FOREIGN_EXCHANGE,
        self::TYPE_UNCLEARED_CHEQUE,
        self::TYPE_DEPOSIT_IN_TRANSIT,
        self::TYPE_MISSING_TRANSACTION,
        self::TYPE_DUPLICATE,
        self::TYPE_REVERSAL,
    ];

    protected $fillable = [
        'company_id',
        'reconciliation_id',
        'type',
        'side',
        'sign',
        'amount',
        'account_id',
        'journal_entry_id',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'reconciliation_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public static function typeLabel(string $type): string
    {
        return [
            self::TYPE_BANK_FEES => 'Bank Fees',
            self::TYPE_INTEREST_EARNED => 'Interest Earned',
            self::TYPE_BOOK_ERROR => 'Book Error',
            self::TYPE_BANK_ERROR => 'Bank Error',
            self::TYPE_FOREIGN_EXCHANGE => 'Foreign Exchange',
            self::TYPE_UNCLEARED_CHEQUE => 'Uncleared Cheque',
            self::TYPE_DEPOSIT_IN_TRANSIT => 'Deposit in Transit',
            self::TYPE_MISSING_TRANSACTION => 'Missing Transaction',
            self::TYPE_DUPLICATE => 'Duplicate',
            self::TYPE_REVERSAL => 'Reversal',
        ][$type] ?? ucfirst($type);
    }
}
