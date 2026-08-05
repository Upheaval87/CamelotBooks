<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitsOfProductionUsageEntry extends Model
{
    use TenantScoped;

    protected $fillable = [
        'asset_id',
        'company_id',
        'period_start_date',
        'period_end_date',
        'units_used',
        'cumulative_units',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'units_used' => 'decimal:2',
        'cumulative_units' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
