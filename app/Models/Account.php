<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'sub_type',
        'description',
        'opening_balance',
        'opening_balance_date',
        'currency',
        'is_bank_account',
        'is_petty_cash',
        'is_active',
        'cash_flow_section',
        'is_non_cash',
        'next_cheque_number',
        'petty_cash_float',
        'is_group',
        'level',
        'allow_posting',
        'is_system_account',
        'normal_balance',
        'posting_behaviour',
        'allow_adjustments',
        'legacy_code',
        'is_contra',
        'sort_order',
        'version',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_bank_account' => 'boolean',
        'is_petty_cash' => 'boolean',
        'is_active' => 'boolean',
        'is_non_cash' => 'boolean',
        'next_cheque_number' => 'integer',
        'petty_cash_float' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_group' => 'boolean',
        'allow_posting' => 'boolean',
        'is_system_account' => 'boolean',
        'allow_adjustments' => 'boolean',
        'is_contra' => 'boolean',
        'sort_order' => 'integer',
        'version' => 'integer',
    ];

    public function getCurrentBalanceAttribute(): float
    {
        $postedSum = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) {
                $q->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED]);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit = (float) ($postedSum->total_debit ?? 0);
        $totalCredit = (float) ($postedSum->total_credit ?? 0);

        $balance = $this->isDebitNormal()
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;

        return (float) number_format($balance + (float) $this->opening_balance, 2, '.', '');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'bank_account_id');
    }

    public function auditTrail(): HasMany
    {
        return $this->hasMany(CoaAuditLog::class);
    }

    public function isControlled(): bool
    {
        return (bool) $this->is_system_account;
    }

    public function isContra(): bool
    {
        return (bool) $this->is_contra;
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    public function isCreditNormal(): bool
    {
        return in_array($this->type, ['liability', 'equity', 'income']);
    }

    public function getFormattedCodeAttribute(): string
    {
        return $this->code;
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function getBalanceForDisplayAttribute(): string
    {
        $balance = $this->current_balance;
        if ($this->isDebitNormal()) {
            return $balance >= 0
                ? number_format($balance, 2)
                : '(' . number_format(abs($balance), 2) . ')';
        }
        return $balance <= 0
            ? number_format(abs($balance), 2)
            : '(' . number_format($balance, 2) . ')';
    }

    public static function verifyAllBalances(): array
    {
        $accounts = static::with('journalEntryLines.journalEntry')
            ->where('is_active', true)
            ->get();

        $totalDebit = 0;
        $totalCredit = 0;
        $discrepancies = [];

        foreach ($accounts as $account) {
            $balance = $account->current_balance;

            if (abs($balance) > 0.001) {
                if ($account->isDebitNormal()) {
                    $totalDebit += $balance;
                } else {
                    $totalCredit += abs($balance);
                }
            }
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            $discrepancies[] = [
                'type' => 'trial_balance_mismatch',
                'message' => "Trial balance does not net to zero. Debit total: " .
                    number_format($totalDebit, 2) . ", Credit total: " .
                    number_format($totalCredit, 2),
                'debit_total' => $totalDebit,
                'credit_total' => $totalCredit,
            ];
        }

        return $discrepancies;
    }
}
