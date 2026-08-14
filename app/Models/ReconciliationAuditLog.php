<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationAuditLog extends Model
{
    use TenantScoped;

    protected $table = 'bank_reconciliation_audit_logs';

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_IMPORTED = 'statement_imported';
    public const ACTION_MATCHED = 'matched';
    public const ACTION_UNMATCHED = 'unmatched';
    public const ACTION_ADJUSTMENT_ADDED = 'adjustment_added';
    public const ACTION_ADJUSTMENT_REMOVED = 'adjustment_removed';
    public const ACTION_READY_FOR_REVIEW = 'ready_for_review';
    public const ACTION_REOPENED = 'reopened';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_REVERSED = 'reversed';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'reconciliation_id',
        'action',
        'details',
        'user_id',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'reconciliation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public static function actionLabel(string $action): string
    {
        return [
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_IMPORTED => 'Statement Imported',
            self::ACTION_MATCHED => 'Matched',
            self::ACTION_UNMATCHED => 'Unmatched',
            self::ACTION_ADJUSTMENT_ADDED => 'Adjustment Added',
            self::ACTION_ADJUSTMENT_REMOVED => 'Adjustment Removed',
            self::ACTION_READY_FOR_REVIEW => 'Ready for Review',
            self::ACTION_REOPENED => 'Reopened',
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_COMPLETED => 'Completed',
            self::ACTION_REVERSED => 'Reversed',
        ][$action] ?? ucfirst($action);
    }
}
