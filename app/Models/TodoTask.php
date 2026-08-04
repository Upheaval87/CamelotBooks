<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class TodoTask extends Model
{
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';

    const GRANULARITY_DAY = 'day';
    const GRANULARITY_WEEK = 'week';
    const GRANULARITY_MONTH = 'month';
    const GRANULARITY_YEAR = 'year';

    /**
     * Deadline grouping buckets used by the active-task list and the
     * dashboard summary. Keys match the group headers in the index view.
     */
    const BUCKET_OVERDUE = 'overdue';
    const BUCKET_TODAY = 'today';
    const BUCKET_THIS_MONTH = 'this_month';
    const BUCKET_THIS_YEAR = 'this_year';
    const BUCKET_NO_DEADLINE = 'no_deadline';

    /**
     * SearchCatalog entity key → model class, used when a task links to a
     * record picked via the scoped-search / global-search infrastructure.
     */
    const LINKABLE_CLASS_MAP = [
        'product' => Product::class,
        'account' => Account::class,
        'customer' => Customer::class,
        'vendor' => Vendor::class,
        'branch' => Branch::class,
        'cost-center' => CostCenter::class,
        'employee' => Employee::class,
        'user' => User::class,
        'bank-account' => Account::class,
        'asset' => Asset::class,
        'asset-category' => AssetCategory::class,
        'payroll-run' => PayrollRun::class,
        'fiscal-year' => FiscalYear::class,
        'invoice' => Invoice::class,
        'bill' => Bill::class,
        'sales-receipt' => SalesReceipt::class,
        'quotation' => Quotation::class,
        'credit-note' => CreditNote::class,
        'vendor-credit' => VendorCredit::class,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'deadline_date',
        'deadline_granularity',
        'priority',
        'status',
        'completed_at',
        'linkable_type',
        'linkable_id',
        'link_label',
        'link_url',
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * End-of-period deadline date for a granularity. A "this month" or
     * "this year" deadline stores the last day of the period so it sorts
     * correctly, while the granularity field keeps the original intent.
     */
    public static function deadlineFor(string $granularity, ?Carbon $now = null): Carbon
    {
        $now = $now ?? now();

        return match ($granularity) {
            self::GRANULARITY_DAY => $now->copy()->startOfDay(),
            self::GRANULARITY_WEEK => $now->copy()->endOfWeek(),
            self::GRANULARITY_MONTH => $now->copy()->endOfMonth(),
            self::GRANULARITY_YEAR => $now->copy()->endOfYear(),
            default => $now->copy()->endOfMonth(),
        };
    }

    /**
     * Deadline urgency bucket for a task. Shared by the index grouping and
     * the dashboard summary so both always agree.
     */
    public static function bucketKey(?Carbon $deadline, ?string $granularity, ?Carbon $now = null): string
    {
        if (!$deadline) {
            return self::BUCKET_NO_DEADLINE;
        }

        $now = $now ?? now();
        $today = $now->copy()->startOfDay();
        $date = $deadline->copy()->startOfDay();

        if ($date->lt($today)) {
            return self::BUCKET_OVERDUE;
        }
        if ($date->equalTo($today)) {
            return self::BUCKET_TODAY;
        }
        if ($granularity === self::GRANULARITY_YEAR) {
            return self::BUCKET_THIS_YEAR;
        }
        if ($granularity === self::GRANULARITY_MONTH || $granularity === self::GRANULARITY_WEEK) {
            return self::BUCKET_THIS_MONTH;
        }
        if ($date->lte($now->copy()->endOfMonth())) {
            return self::BUCKET_THIS_MONTH;
        }

        return self::BUCKET_THIS_YEAR;
    }

    /**
     * Human-readable deadline label for list rows.
     */
    public function deadlineLabel(): string
    {
        if (!$this->deadline_date) {
            return __('No deadline');
        }

        $today = now()->startOfDay();
        $date = $this->deadline_date->copy()->startOfDay();

        if ($date->lt($today)) {
            return __('Overdue');
        }
        if ($date->equalTo($today)) {
            return __('Due today');
        }
        if ($this->deadline_granularity === self::GRANULARITY_WEEK) {
            return __('Due this week');
        }
        if ($this->deadline_granularity === self::GRANULARITY_MONTH) {
            return __('Due this month');
        }
        if ($this->deadline_granularity === self::GRANULARITY_YEAR) {
            return __('Due this year');
        }

        return __('Due :date', ['date' => $this->deadline_date->format('M j, Y')]);
    }

    public function isOverdue(?Carbon $now = null): bool
    {
        if (!$this->deadline_date) {
            return false;
        }

        return $this->deadline_date->copy()->startOfDay()->lt(
            ($now ?? now())->copy()->startOfDay()
        );
    }
}
