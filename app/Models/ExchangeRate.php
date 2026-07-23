<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'company_id',
        'currency_from',
        'currency_to',
        'rate',
        'effective_date',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'effective_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeLatestBefore($query, string $currencyFrom, string $currencyTo, string $date)
    {
        return $query->where('currency_from', $currencyFrom)
            ->where('currency_to', $currencyTo)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date');
    }

    public static function getRate(int $companyId, string $from, string $to, string $date): ?float
    {
        if (strtoupper($from) === strtoupper($to)) {
            return 1.0;
        }

        $rate = static::where('company_id', $companyId)
            ->where('currency_from', $from)
            ->where('currency_to', $to)
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->first();

        return $rate ? (float) $rate->rate : null;
    }
}
