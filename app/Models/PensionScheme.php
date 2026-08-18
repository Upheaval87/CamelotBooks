<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PensionScheme extends Model
{
    use TenantScoped;

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
        'employee_rate'            => 'decimal:2',
        'employer_rate'            => 'decimal:2',
        'max_contributory_salary' => 'decimal:2',
        'effective_from'          => 'date',
        'effective_to'            => 'date',
        'is_current'              => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function calculateEmployeeContribution(float $grossPay): float
    {
        $salary = $this->max_contributory_salary ? min($grossPay, (float) $this->max_contributory_salary) : $grossPay;
        return round($salary * (float) $this->employee_rate / 100, 2);
    }

    public function calculateEmployerContribution(float $grossPay): float
    {
        $salary = $this->max_contributory_salary ? min($grossPay, (float) $this->max_contributory_salary) : $grossPay;
        return round($salary * (float) $this->employer_rate / 100, 2);
    }
}
