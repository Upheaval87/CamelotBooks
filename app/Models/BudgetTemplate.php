<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTemplate extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'name', 'description', 'basis', 'source_budget_id', 'lines_count', 'template_data', 'created_by',
    ];

    protected $casts = [
        'template_data' => 'array',
    ];

    public const BASES = [
        'blank'           => 'Blank Template',
        'prior_actuals'   => 'Based on Prior Year Actuals',
        'standard'        => 'Standard Budget',
        'zero_based'      => 'Zero-Based Budget',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'source_budget_id');
    }

    public function basisLabel(): string
    {
        return self::BASES[$this->basis] ?? ($this->basis ?? 'Custom');
    }
}
