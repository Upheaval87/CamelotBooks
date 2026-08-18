<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAllowance extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'gl_account_id',
        'is_taxable',
        'is_active',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
