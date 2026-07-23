<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryItem extends Model
{
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

    public function companyAllowance(): BelongsTo
    {
        return $this->belongsTo(CompanyAllowance::class);
    }
}
