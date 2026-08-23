<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTransaction extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'period_id',
        'tax_code_id',
        'rate_pct',
        'side',
        'source_kind',
        'source_id',
        'base_amount',
        'tax_amount',
        'gross_amount',
        'net_amount',
        'exemption_id',
        'exemption_reason',
        'apportionment_pct',
        'recoverable_tax_amount',
        'jurisdiction_id',
        'gl_account_id',
        'recognition_basis',
        'recognized_at',
        'is_reversal',
        'reverses_transaction_id',
        'status',
    ];

    protected $casts = [
        'rate_pct' => 'decimal:4',
        'base_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'recoverable_tax_amount' => 'decimal:2',
        'apportionment_pct' => 'decimal:3',
        'recognized_at' => 'datetime',
        'is_reversal' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function exemption(): BelongsTo
    {
        return $this->belongsTo(TaxExemption::class);
    }

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function reversesTransaction(): BelongsTo
    {
        return $this->belongsTo(TaxTransaction::class, 'reverses_transaction_id');
    }

    public function scopeOutput(Builder $q): void
    {
        $q->where('side', 'OUTPUT');
    }

    public function scopeInput(Builder $q): void
    {
        $q->where('side', 'INPUT');
    }

    public function scopePosted(Builder $q): void
    {
        $q->where('status', 'POSTED');
    }

    public function scopeForPeriod(Builder $q, $periodId): void
    {
        $q->where('period_id', $periodId);
    }
}
