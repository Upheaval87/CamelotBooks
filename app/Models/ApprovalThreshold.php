<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalThreshold extends Model
{
    protected $fillable = [
        'company_id',
        'document_type',
        'threshold_amount',
        'is_active',
    ];

    protected $casts = [
        'threshold_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * All supported document types with human-readable labels.
     */
    public static function documentTypes(): array
    {
        return [
            'journal_entry' => 'Journal Entries',
            'bill' => 'Bills',
            'expense' => 'Expenses',
            'purchase_requisition' => 'Purchase Requisitions',
            'payroll_run' => 'Payroll Runs',
            'budget' => 'Budgets',
        ];
    }

    /**
     * Check if approval is required for a given document type and amount.
     * Falls back to the global ApprovalSetting if no threshold is configured for this type.
     */
    public static function isApprovalRequired(int $companyId, string $documentType, float $amount = 0): bool
    {
        $global = ApprovalSetting::where('company_id', $companyId)->first();
        if (!$global || !$global->requires_approval) {
            return false;
        }

        $threshold = static::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if (!$threshold) {
            // No per-type threshold configured — use global threshold
            if ($global->threshold_amount <= 0) {
                return true;
            }
            return $amount >= $global->threshold_amount;
        }

        if ($threshold->threshold_amount <= 0) {
            return true; // threshold 0 means always require approval
        }

        return $amount >= $threshold->threshold_amount;
    }

    /**
     * Get all thresholds for a company as [document_type => ApprovalThreshold].
     */
    public static function getAllForCompany(int $companyId): array
    {
        return static::where('company_id', $companyId)
            ->get()
            ->keyBy('document_type')
            ->toArray();
    }
}
