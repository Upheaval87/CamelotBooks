<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'name', 'code', 'type', 'fiscal_year_id', 'period',
        'department', 'branch_id', 'project', 'cost_center_id', 'status',
        'currency', 'total_income', 'total_expenses',
        'prepared_by', 'approved_by', 'approved_at',
        'locked_by', 'locked_at', 'rejection_reason', 'approval_chain',
    ];

    protected $casts = [
        'total_income'     => 'decimal:2',
        'total_expenses'   => 'decimal:2',
        'approved_at'      => 'datetime',
        'locked_at'        => 'datetime',
        'approval_chain'   => 'array',
    ];

    public const TYPES = [
        'operating'  => 'Operating Budget',
        'capital'    => 'Capital Budget',
        'project'    => 'Project Budget',
        'department' => 'Department Budget',
        'cash_flow'  => 'Cash Flow Budget',
    ];

    public const PERIODS = [
        'annual'    => 'Annual',
        'quarterly' => 'Quarterly',
        'monthly'   => 'Monthly',
        'custom'    => 'Custom',
    ];

    public const STATUSES = [
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved'         => 'Approved',
        'locked'           => 'Locked',
        'rejected'         => 'Rejected',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(BudgetAdjustment::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(BudgetAlert::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(BudgetAuditLog::class);
    }

    public function preparedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForFiscalYear($query, int $fiscalYearId)
    {
        return $query->where('fiscal_year_id', $fiscalYearId);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getUtilizationAttribute(): float
    {
        if ($this->total_expenses <= 0) {
            return 0;
        }
        // For expense budgets: spent / budgeted (actuals computed live)
        return 0; // actuals always computed live from GL
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function isSubmittable(): bool
    {
        return $this->status === 'draft';
    }

    public function isLockable(): bool
    {
        return $this->status === 'approved';
    }

    public function totalBudgetAttribute(): float
    {
        return (float) $this->total_income;
    }

    public function totalSpentAttribute(): float
    {
        // Actuals always computed live from GL — never stored
        return 0;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'            => 'steel',
            'pending_approval' => 'pending',
            'approved'         => 'active',
            'locked'           => 'locked',
            'rejected'         => 'over',
            default            => 'steel',
        };
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function periodLabel(): string
    {
        return self::PERIODS[$this->period] ?? $this->period;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getNetAmountAttribute(): float
    {
        return (float) $this->total_income - (float) $this->total_expenses;
    }

    public function monthlyBreakdown(): array
    {
        $monthly = [];
        $monthlyAmount = $this->annual_amount ?? ($this->total_income + $this->total_expenses);

        if ($this->lines->isEmpty()) {
            for ($m = 1; $m <= 12; $m++) {
                $monthly[$m] = 0;
            }
            return $monthly;
        }

        for ($m = 1; $m <= 12; $m++) {
            $monthly[$m] = 0;
        }

        foreach ($this->lines as $line) {
            $lineAmount = (float) $line->annual_amount;
            if ($line->distribution === 'even') {
                $perMonth = $lineAmount / 12;
                for ($m = 1; $m <= 12; $m++) {
                    $monthly[$m] += $perMonth;
                }
            } elseif ($line->distribution === 'seasonal') {
                $seasonalWeights = [0.07, 0.07, 0.08, 0.08, 0.09, 0.09, 0.09, 0.09, 0.08, 0.08, 0.08, 0.08];
                for ($m = 0; $m < 12; $m++) {
                    $monthly[$m + 1] += $lineAmount * ($seasonalWeights[$m] ?? (1/12));
                }
            } else {
                $perMonth = $lineAmount / 12;
                for ($m = 1; $m <= 12; $m++) {
                    $monthly[$m] += $perMonth;
                }
            }
        }

        return $monthly;
    }
}
