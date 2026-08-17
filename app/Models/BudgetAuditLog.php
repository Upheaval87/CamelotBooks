<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BudgetAuditLog extends Model
{
    use TenantScoped;

    protected $table = 'budget_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'company_id', 'budget_id', 'user_id', 'action',
        'before', 'after', 'description', 'created_at',
    ];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTIONS = [
        'created'     => 'Created',
        'updated'     => 'Updated',
        'submitted'   => 'Submitted for Approval',
        'approved'    => 'Approved',
        'rejected'    => 'Rejected',
        'locked'      => 'Locked',
        'unlocked'    => 'Unlocked',
        'adjustment'  => 'Adjustment',
        'transfer'    => 'Transfer',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
