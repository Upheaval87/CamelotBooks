<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\Auth\PasswordResetCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerifyCodeController extends Controller
{
    public function __construct(
        protected PasswordResetCodeService $codes,
    ) {}

    /**
     * Display the verification code entry page. The page is driven by the
     * email stored in the session when the reset was requested, so it can only
     * be reached after submitting the forgot-password form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $code = $this->codes->latestPending($user);

        if (! $code) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-code', [
            'maskedEmail' => $this->codes->maskEmail($user->email),
            'expiresAt' => $code->expires_at,
        ]);
    }

    /**
     * Verify the submitted code against the latest pending code for the
     * session's email. On success a fresh password-reset token is minted and
     * the user is forwarded to the reset-password form (the same destination
     * the old emailed link used to point at).
     *
     * @throws ValidationException
     */
    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $email = $request->session()->get('password_reset_email');
        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user || ! $this->codes->verify($user, $request->string('code')->toString())) {
            return $this->fail('That code isn\'t valid.');
        }

        $request->session()->forget('password_reset_email');

        $token = Password::broker()->createToken($user);

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email], false);

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $resetUrl]);
        }

        return redirect($resetUrl);
    }

    /**
     * Issue a fresh code for the session's email, gated by the per-user resend
     * cooldown (in addition to the route throttle). Returns the new expiry so
     * the page can restart its countdown.
     */
    public function resend(Request $request): JsonResponse
    {
        $email = $request->session()->get('password_reset_email');
        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user) {
            return $this->fail('That code isn\'t valid.');
        }

        if ($cooldownEndsAt = $this->codes->cooldownEndsAt($user)) {
            return response()->json([
                'message' => 'Please wait a moment before requesting another code.',
                'retry_after' => (int) max(1, $cooldownEndsAt->diffInSeconds(now())),
            ], 429);
        }

        $result = $this->codes->issue($user);

        Mail::to($user)->queue(new VerificationCodeMail(
            code: $result['code'],
            maskedEmail: $this->codes->maskEmail($user->email),
            expiresAt: $result['expires_at'],
        ));

        return response()->json([
            'expires_at' => $result['expires_at']->toIso8601String(),
            'resend_after' => $this->codes->resendCooldownSeconds(),
        ]);
    }

    /**
     * Cancel the in-progress verification: forget the stored email so the
     * verify/resend endpoints become inert and the page redirects to the
     * forgot-password form.
     */
    public function cancel(Request $request): JsonResponse
    {
        $request->session()->forget('password_reset_email');

        return response()->json(['status' => 'cancelled']);
    }

    /**
     * @throws ValidationException
     */
    protected function fail(string $message): JsonResponse
    {
        throw ValidationException::withMessages(['code' => [$message]]);
    }
}
