<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'filename',
        'statement_date',
        'statement_end_balance',
        'line_count',
        'imported_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_end_balance' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'import_id');
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
