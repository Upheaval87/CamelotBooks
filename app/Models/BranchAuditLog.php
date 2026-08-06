<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record of a branch creation (see the matching tenant
 * migration for the schema rationale). TenantScoped because branches live in
 * the tenant database; legacy shared-DB companies write to the shared DB.
 */
class BranchAuditLog extends Model
{
    use TenantScoped;

    protected $table = 'branch_audit_log';

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_COMPANY_MANAGER = 'company_manager';

    public const UPDATED_AT = null;

    protected $fillable = [
        'branch_id',
        'company_id',
        'created_by_user_id',
        'created_by_role',
        'was_override',
        'branch_limit_at_creation',
        'branch_count_at_creation',
        'created_at',
    ];

    protected $casts = [
        'was_override' => 'boolean',
        'branch_limit_at_creation' => 'integer',
        'branch_count_at_creation' => 'integer',
        'created_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
