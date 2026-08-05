<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CENTRAL platform-level audit log for Super Admin actions.
 *
 * Deliberately NOT TenantScoped: it always resolves to the default (central)
 * connection, so it can never be confused with a tenant's own `audit_logs`
 * table (which IS TenantScoped and lives in each company's tenant database).
 */
class SuperAdminAuditLog extends Model
{
    public const ACTION_COMPANY_CREATED = 'company.created';
    public const ACTION_COMPANY_SUSPENDED = 'company.suspended';
    public const ACTION_COMPANY_REACTIVATED = 'company.reactivated';
    public const ACTION_COMPANY_PROVISION_FAILED = 'company.provision_failed';
    public const ACTION_MODULE_ENABLED = 'module.enabled';
    public const ACTION_MODULE_DISABLED = 'module.disabled';
    public const ACTION_USER_CREATED = 'user.created';
    public const ACTION_USER_UPDATED = 'user.updated';
    public const ACTION_USER_DEACTIVATED = 'user.deactivated';
    public const ACTION_USER_REACTIVATED = 'user.reactivated';
    public const ACTION_USER_PASSWORD_RESET = 'user.password_reset';
    public const ACTION_ASSIGNMENT_CREATED = 'assignment.created';
    public const ACTION_ASSIGNMENT_UPDATED = 'assignment.updated';
    public const ACTION_ASSIGNMENT_DELETED = 'assignment.deleted';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'target_type',
        'target_id',
        'before',
        'after',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function log(
        int $actorId,
        string $action,
        ?int $companyId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $description = null
    ): self {
        return static::create([
            'user_id' => $actorId,
            'company_id' => $companyId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before' => $before,
            'after' => $after,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
