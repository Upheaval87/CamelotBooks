<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BudgetAlert extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'rule_id', 'budget_id', 'budget_line_id',
        'severity', 'message', 'is_read', 'sent_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public const SEVERITIES = [
        'exceeded' => 'Exceeded',
        'nearing'  => 'Nearing Limit',
        'unusual'  => 'Unusual',
    ];

    public function rule()
    {
        return $this->belongsTo(BudgetAlertRule::class, 'rule_id');
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function budgetLine()
    {
        return $this->belongsTo(BudgetLine::class, 'budget_line_id');
    }

    public function severityLabel(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }
}
