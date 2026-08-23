<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxApportionmentRule extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'tax_type_id',
        'jurisdiction_id',
        'method',
        'recoverable_pct',
        'effective_from',
        'effective_to',
        'note',
    ];

    protected $casts = [
        'recoverable_pct' => 'decimal:3',
        'effective_from' => 'date',
        'effective_to' => 'date',
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

    public function scopeEffective(Builder $q, $date): void
    {
        $on = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date;

        $q->where('effective_from', '<=', $on)
            ->where(function (Builder $inner) use ($on) {
                $inner->whereNull('effective_to')->orWhere('effective_to', '>=', $on);
            });
    }
}
