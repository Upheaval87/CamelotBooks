<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'budget_id', 'line_type', 'account_id',
        'annual_amount', 'monthly_amount', 'distribution', 'distribution_config',
        'department', 'branch_id', 'project', 'cost_center_id',
    ];

    protected $casts = [
        'annual_amount'      => 'decimal:2',
        'monthly_amount'     => 'decimal:2',
        'distribution_config' => 'array',
    ];

    public const LINE_TYPES = [
        'income'  => 'Income',
        'expense' => 'Expense',
    ];

    public const DISTRIBUTIONS = [
        'even'     => 'Even Distribution',
        'seasonal' => 'Seasonal',
        'custom'   => 'Custom',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function lineTypeLabel(): string
    {
        return self::LINE_TYPES[$this->line_type] ?? $this->line_type;
    }

    public function distributionLabel(): string
    {
        return self::DISTRIBUTIONS[$this->distribution] ?? $this->distribution;
    }

    public function monthlyBreakdown(): array
    {
        if ($this->distribution === 'even') {
            $monthly = $this->annual_amount / 12;
            return array_fill(0, 12, round($monthly, 2));
        }

        if ($this->distribution === 'seasonal' && $this->distribution_config) {
            $percentages = $this->distribution_config['months'] ?? array_fill(0, 12, 100 / 12);
            return array_map(fn($pct) => round($this->annual_amount * $pct / 100, 2), $percentages);
        }

        if ($this->distribution === 'custom' && $this->distribution_config) {
            $amounts = $this->distribution_config['months'] ?? [];
            return array_pad($amounts, 12, 0);
        }

        // Fallback: even
        $monthly = $this->annual_amount / 12;
        return array_fill(0, 12, round($monthly, 2));
    }
}
