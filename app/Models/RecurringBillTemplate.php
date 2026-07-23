<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringBillTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'vendor_id',
        'name',
        'memo',
        'frequency',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'next_run_date',
        'auto_post',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'auto_post' => 'boolean',
        'is_active' => 'boolean',
    ];

    const FREQ_WEEKLY = 'weekly';
    const FREQ_BIWEEKLY = 'biweekly';
    const FREQ_MONTHLY = 'monthly';
    const FREQ_QUARTERLY = 'quarterly';
    const FREQ_YEARLY = 'yearly';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateLines(): HasMany
    {
        return $this->hasMany(RecurringBillTemplateLine::class, 'rbt_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'recurring_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }
}
