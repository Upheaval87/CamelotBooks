<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PensionScheme extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'registration_number',
        'employee_rate',
        'employer_rate',
        'max_contributory_salary',
        'effective_from',
        'effective_to',
        'is_current',
    ];

    protected $casts = [
        'employee_rate' => 'decimal:2',
        'employer_rate' => 'decimal:2',
        'max_contributory_salary' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_current' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeEffectiveForDate($query, string $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            });
    }
}
