<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Central. Authoritative record of which users may access which companies and
 * with what role/branch scope (replaces the legacy company_user pivot going
 * forward). Never uses the TenantScoped trait.
 */
class UserCompanyAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'role',
        'branch_ids',
        'is_active',
    ];

    protected $casts = [
        'branch_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
