<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversalAuthorizationRule extends Model
{
    use TenantScoped;

    protected $table = 'reversal_authorization_rules';

    protected $fillable = [
        'company_id', 'transaction_type', 'minimum_amount',
        'maximum_amount', 'required_approvals', 'approver_role',
        'branch_id', 'active',
    ];

    protected $casts = [
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'required_approvals' => 'integer',
        'active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForAmount($query, float $amount)
    {
        return $query->where('minimum_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $amount);
            });
    }

    public function scopeForType($query, ?string $type)
    {
        if ($type) {
            $query->where(function ($q) use ($type) {
                $q->whereNull('transaction_type')->orWhere('transaction_type', $type);
            });
        }
        return $query;
    }

    public function amountRangeLabel(): string
    {
        $min = number_format($this->minimum_amount, 0);
        $max = $this->maximum_amount ? number_format($this->maximum_amount, 0) : 'No limit';
        return "K{$min} – K{$max}";
    }
}
