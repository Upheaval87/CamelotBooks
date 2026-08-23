<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRegistration extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'entity_kind',
        'entity_id',
        'jurisdiction_id',
        'tax_type_id',
        'reg_number',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class);
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function scopeActive(Builder $q): void
    {
        $today = today()->format('Y-m-d');

        $q->where('status', 'active')
            ->where('effective_from', '<=', $today)
            ->where(function (Builder $inner) use ($today) {
                $inner->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            });
    }
}
