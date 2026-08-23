<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxCodeRate extends Model
{
    use TenantScoped;

    protected $fillable = [
        'tax_code_id',
        'rate_pct',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'rate_pct' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
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
