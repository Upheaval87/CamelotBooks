<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSalaryStructure extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'employee_id',
        'basic_pay',
        'effective_from',
        'effective_to',
        'is_current',
    ];

    protected $casts = [
        'basic_pay'      => 'decimal:2',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_current'     => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EmployeeSalaryItem::class, 'salary_structure_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
