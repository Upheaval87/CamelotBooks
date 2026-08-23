<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TaxCode extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'tax_type_id',
        'jurisdiction_id',
        'treatment',
        'price_basis',
        'rounding_mode',
        'rounding_level',
        'gl_output_acct',
        'gl_input_acct',
        'gl_payable_acct',
        'effective_from',
        'effective_to',
        'active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(TaxCodeRate::class);
    }

    public function glOutputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_output_acct');
    }

    public function glInputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_input_acct');
    }

    public function glPayableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_payable_acct');
    }

    public function taxTransactions(): HasMany
    {
        return $this->hasMany(TaxTransaction::class);
    }

    public function activeRate(?string $date = null): ?TaxCodeRate
    {
        $on = Carbon::parse($date ?? today())->format('Y-m-d');

        return $this->rates()
            ->where('effective_from', '<=', $on)
            ->where(function (Builder $q) use ($on) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $on);
            })
            ->first();
    }
}
