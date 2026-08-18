<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryItem extends Model
{
    use TenantScoped;

    protected $fillable = [
        'salary_structure_id',
        'company_allowance_id',
        'type',
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'salary_structure_id');
    }

    public function allowance(): BelongsTo
    {
        return $this->belongsTo(CompanyAllowance::class, 'company_allowance_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->whereHas('salaryStructure', fn($q) => $q->where('company_id', $companyId));
    }
}
