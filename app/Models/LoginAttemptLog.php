<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Central. Immutable log of every login attempt (success and failure), kept
 * deliberately separate from the per-tenant audit_logs and always written to
 * the central database. Never uses the TenantScoped trait.
 */
class LoginAttemptLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'ip',
        'user_agent',
        'success',
        'user_id',
        'failure_reason',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public static function record(?string $email, ?string $ip, ?string $userAgent, bool $success, ?int $userId = null, ?string $failureReason = null): self
    {
        return static::create([
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent !== null ? substr($userAgent, 0, 500) : null,
            'success' => $success,
            'user_id' => $userId,
            'failure_reason' => $failureReason,
        ]);
    }
}
