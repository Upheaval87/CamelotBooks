<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBeneficiary extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'employee_id',
        'full_name',
        'relationship',
        'phone',
        'pct',
        'sort_order',
    ];

    protected $casts = [
        'pct' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
