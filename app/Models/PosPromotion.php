<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;

class PosPromotion extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'discount_value',
        'min_qty',
        'max_qty',
        'customer_group',
        'start_date',
        'end_date',
        'is_active',
        'applies_to',
        'applies_to_ids',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_value' => 'decimal:2',
        'applies_to_ids' => 'array',
    ];

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyActive($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }
}
