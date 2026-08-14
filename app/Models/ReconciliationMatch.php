<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationMatch extends Model
{
    use TenantScoped;

    protected $table = 'bank_reconciliation_matches';

    public const TYPE_ONE_TO_ONE = 'one_to_one';
    public const TYPE_ONE_TO_MANY = 'one_to_many';
    public const TYPE_MANY_TO_ONE = 'many_to_one';

    protected $fillable = [
        'company_id',
        'reconciliation_id',
        'bank_statement_line_id',
        'bank_transaction_id',
        'method',
        'confidence',
        'created_by',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'reconciliation_id');
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id');
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
