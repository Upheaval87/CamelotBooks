<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
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
        'is_active',
        'cash_flow_section',
        'is_non_cash',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_bank_account' => 'boolean',
        'is_active' => 'boolean',
        'is_non_cash' => 'boolean',
        'opening_balance_date' => 'date',
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
        $accounts = static::with('journalEntryLines.journalEntry')->get();
        $discrepancies = [];

        foreach ($accounts as $account) {
            $dbBalance = (float) DB::table('accounts')->where('id', $account->id)->value('current_balance');
            $computedBalance = $account->current_balance;

            if (round($dbBalance, 2) !== round($computedBalance, 2)) {
                $discrepancies[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'db_balance' => $dbBalance,
                    'computed_balance' => $computedBalance,
                ];
            }
        }

        return $discrepancies;
    }
}
