<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Central. An open super-admin support session records WHICH user is currently
 * acting inside WHICH company (impersonation/support access). The session is
 * closed when the admin switches company, logs in elsewhere, or logs out, and
 * the duration is computed from started_at/ended_at. Never uses the
 * TenantScoped trait.
 */
class CompanySupportSession extends Model
{
    public const ENDED_LOGOUT = 'logout';

    public const ENDED_CONTEXT_CHANGED = 'context_changed';

    public const ENDED_SUPER_ADMIN_REVOKED = 'super_admin_revoked';

    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'company_id',
        'started_at',
        'ended_at',
        'ended_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDurationAttribute(): ?string
    {
        $end = $this->ended_at ?? now();

        return $this->started_at->diffForHumans($end, true);
    }
}
