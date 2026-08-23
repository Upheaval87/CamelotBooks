<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversalApprovalHistory extends Model
{
    use TenantScoped;

    protected $table = 'reversal_approval_history';
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'reversal_request_id', 'action',
        'performed_by', 'remarks', 'date_time',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TransactionReversalRequest::class, 'reversal_request_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'requested' => 'Requested',
            'reviewed' => 'Reviewed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'clarification_requested' => 'Clarification Requested',
            'posted_to_gl' => 'Posted to GL',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }
}
