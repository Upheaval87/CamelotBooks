<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A hashed 6-digit verification code issued during the code-based password
 * reset flow. Deliberately central (not TenantScoped): password reset happens
 * against the central users table before any company context exists.
 */
class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isValidFor(string $code): bool
    {
        return ! $this->is_expired
            && hash_equals($this->code_hash, static::hashCode($code));
    }
}
