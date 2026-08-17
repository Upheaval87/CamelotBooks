<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BudgetAlertRule extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'name', 'is_active', 'rule_type',
        'warn_threshold', 'exceed_threshold', 'unusual_multiplier',
        'low_balance_threshold', 'scope', 'channels', 'recipient_ids',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'warn_threshold'       => 'decimal:2',
        'exceed_threshold'     => 'decimal:2',
        'unusual_multiplier'   => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
        'channels'             => 'array',
        'recipient_ids'        => 'array',
    ];

    public const RULE_TYPES = [
        'threshold'  => 'Threshold Alert',
        'unusual'    => 'Unusual Spending',
        'low_balance' => 'Low Balance',
    ];

    public const SCOPES = [
        'budget'      => 'Budget-Wide',
        'department'  => 'Department',
        'line'        => 'Line Item',
    ];

    public function alerts()
    {
        return $this->hasMany(BudgetAlert::class, 'rule_id');
    }

    public function ruleTypeLabel(): string
    {
        return self::RULE_TYPES[$this->rule_type] ?? $this->rule_type;
    }

    public function typeLabel(): string
    {
        return $this->ruleTypeLabel();
    }

    public function scopeLabel(): string
    {
        return self::SCOPES[$this->scope] ?? $this->scope;
    }
}
