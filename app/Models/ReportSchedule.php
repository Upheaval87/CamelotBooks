<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use TenantScoped;

    public const FREQ_DAILY = 'DAILY';
    public const FREQ_WEEKLY = 'WEEKLY';
    public const FREQ_MONTHLY = 'MONTHLY';

    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'report_key',
        'filters',
        'frequency',
        'recipients',
        'format',
        'active',
        'created_by',
        'last_run_at',
        'last_run_status',
        'last_error',
    ];

    protected $casts = [
        'filters' => 'array',
        'recipients' => 'array',
        'active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to active schedules only.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Returns true if this schedule is due to run now based on its frequency.
     */
    public function isDue(): bool
    {
        if (!$this->active) {
            return false;
        }

        $lastRun = $this->last_run_at;

        if (!$lastRun) {
            return true;
        }

        return match ($this->frequency) {
            self::FREQ_DAILY => $lastRun->diffInHours(now()) >= 24,
            self::FREQ_WEEKLY => $lastRun->diffInDays(now()) >= 7,
            self::FREQ_MONTHLY => $lastRun->diffInDays(now()) >= 30,
            default => false,
        };
    }
}
