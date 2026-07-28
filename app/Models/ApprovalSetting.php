<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalSetting extends Model
{
    protected $fillable = [
        'company_id',
        'requires_approval',
        'threshold_amount',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'threshold_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isApprovalRequired(float $amount = 0): bool
    {
        if (!$this->requires_approval) {
            return false;
        }
        if ($this->threshold_amount <= 0) {
            return true;
        }
        return $amount >= $this->threshold_amount;
    }

    /**
     * Check approval using per-document-type thresholds (delegates to ApprovalThreshold).
     */
    public function isApprovalRequiredForType(string $documentType, float $amount = 0): bool
    {
        return \App\Models\ApprovalThreshold::isApprovalRequired($this->company_id, $documentType, $amount);
    }
}
