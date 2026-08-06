<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Carbon;

/**
 * Issues, stores, and verifies the 6-digit verification codes used by the
 * code-based password reset flow. Codes are stored hashed (SHA-256), expire
 * after a configurable TTL, and a per-user cooldown gates resend requests.
 */
class PasswordResetCodeService
{
    public function __construct(
        protected int $codeLength = 6,
        protected int $ttlSeconds = 600,
        protected int $resendCooldownSeconds = 30,
    ) {
        $this->codeLength = (int) config('verification_codes.code_length', $this->codeLength);
        $this->ttlSeconds = (int) config('verification_codes.ttl_seconds', $this->ttlSeconds);
        $this->resendCooldownSeconds = (int) config('verification_codes.resend_cooldown_seconds', $this->resendCooldownSeconds);
    }

    /**
     * Issue a fresh code for the user, invalidating any previously pending
     * codes. The plaintext code is returned for the mailer and is never stored.
     *
     * @return array{code: string, expires_at: Carbon}
     */
    public function issue(User $user): array
    {
        $this->invalidatePending($user);

        $code = str_pad(
            (string) random_int(0, 10 ** $this->codeLength - 1),
            $this->codeLength,
            '0',
            STR_PAD_LEFT
        );

        $expiresAt = now()->addSeconds($this->ttlSeconds);

        VerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => VerificationCode::hashCode($code),
            'expires_at' => $expiresAt,
        ]);

        return ['code' => $code, 'expires_at' => $expiresAt];
    }

    public function latestPending(User $user): ?VerificationCode
    {
        return VerificationCode::query()
            ->where('user_id', $user->id)
            ->pending()
            ->latest('id')
            ->first();
    }

    /**
     * Verify a submitted code against the user's latest pending code. On
     * success the code is marked used and all other pending codes are wiped.
     */
    public function verify(User $user, string $code): bool
    {
        $record = $this->latestPending($user);

        if (! $record || ! $record->isValidFor($code)) {
            return false;
        }

        $record->update(['used_at' => now()]);
        $this->invalidatePending($user, exceptId: $record->id);

        return true;
    }

    /**
     * When (if ever) the user may request a resend. Returns null when a resend
     * is allowed right now.
     */
    public function cooldownEndsAt(User $user): ?Carbon
    {
        $last = VerificationCode::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $last) {
            return null;
        }

        $endsAt = $last->created_at->addSeconds($this->resendCooldownSeconds);

        return $endsAt->isFuture() ? $endsAt : null;
    }

    public function resendCooldownSeconds(): int
    {
        return $this->resendCooldownSeconds;
    }

    /**
     * Mask an email for display, keeping only the first character of the local
     * part and the full domain: "jane@example.com" → "j***@example.com".
     */
    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return $email;
        }

        [$local, $domain] = $parts;

        if ($local === '') {
            return $email;
        }

        $maskedLocal = mb_substr($local, 0, 1).'***';

        return $maskedLocal.'@'.$domain;
    }

    protected function invalidatePending(User $user, ?int $exceptId = null): void
    {
        $query = VerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at');

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->delete();
    }
}
