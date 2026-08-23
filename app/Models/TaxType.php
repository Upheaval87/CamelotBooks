<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TaxType extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'category',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxCodes(): HasMany
    {
        return $this->hasMany(TaxCode::class);
    }

    public function taxPeriods(): HasMany
    {
        return $this->hasMany(TaxPeriod::class);
    }

    public function recognitionRule(): HasOne
    {
        return $this->hasOne(TaxRecognitionRule::class);
    }

    public function apportionmentRules(): HasMany
    {
        return $this->hasMany(TaxApportionmentRule::class);
    }
}
