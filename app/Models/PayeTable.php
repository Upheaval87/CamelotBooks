<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayeTable extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'version_name',
        'effective_from',
        'effective_to',
        'is_current',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_current'     => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bands(): HasMany
    {
        return $this->hasMany(PayeTableBand::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function calculatePaye(float $taxableIncome): float
    {
        $bands = $this->bands()->orderBy('sort_order')->get();
        $paye = 0;
        $remaining = $taxableIncome;

        foreach ($bands as $band) {
            if ($remaining <= 0) break;

            $bandMin = (float) $band->threshold;
            $bandMax = $band->upper_limit !== null ? (float) $band->upper_limit : PHP_FLOAT_MAX;
            $rate = (float) $band->rate / 100;

            $bandWidth = $bandMax - $bandMin;
            $taxableInBand = min($remaining, max(0, $bandWidth + ($taxableIncome > $bandMin ? 0 : $bandMin - $taxableIncome)));

            if ($taxableIncome > $bandMin) {
                $taxableInBand = min($remaining, $bandMax > 0 ? $bandMax - max($bandMin, $taxableIncome - $remaining) : $remaining);
                $taxableInBand = max(0, min($taxableInBand, $remaining));
                $paye += $taxableInBand * $rate;
                $remaining -= $taxableInBand;
            }
        }

        return round($paye, 2);
    }
}
