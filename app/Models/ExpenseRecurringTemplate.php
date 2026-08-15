<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseRecurringTemplate extends Model
{
    use TenantScoped;

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_YEARLY = 'yearly';

    const FREQUENCY_LABELS = [
        self::FREQUENCY_DAILY => 'Daily',
        self::FREQUENCY_WEEKLY => 'Weekly',
        self::FREQUENCY_MONTHLY => 'Monthly',
        self::FREQUENCY_QUARTERLY => 'Quarterly',
        self::FREQUENCY_YEARLY => 'Yearly',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'category_id',
        'vendor_id',
        'description',
        'amount',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_run',
        'is_active',
        'branch_id',
        'cost_center_id',
        'expense_account_id',
        'currency',
        'exchange_rate',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interval' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run' => 'date',
        'is_active' => 'boolean',
        'exchange_rate' => 'decimal:6',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCY_LABELS[$this->frequency] ?? ucfirst((string) $this->frequency);
    }

    public function nextRunFrom($date): ?CarbonImmutable
    {
        $cursor = CarbonImmutable::parse($date);

        return match ($this->frequency) {
            self::FREQUENCY_DAILY => $cursor->addDays($this->interval),
            self::FREQUENCY_WEEKLY => $cursor->addWeeks($this->interval),
            self::FREQUENCY_QUARTERLY => $cursor->addMonths($this->interval * 3),
            self::FREQUENCY_YEARLY => $cursor->addYears($this->interval),
            default => $cursor->addMonths($this->interval),
        };
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
