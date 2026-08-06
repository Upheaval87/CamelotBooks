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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset request.
     *
     * Issues a 6-digit verification code (mailed to the account) instead of an
     * emailed link. The response is deliberately identical whether or not the
     * submitted email belongs to a registered account, so this endpoint cannot
     * be used to enumerate which addresses are in the system. The account email
     * is remembered in the session so the verify-code page can show the masked
     * address and confirm the code without it ever appearing in a URL.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $request->session()->put('password_reset_email', $user->email);

            $result = app(PasswordResetCodeService::class)->issue($user);

            Mail::to($user)->queue(new VerificationCodeMail(
                code: $result['code'],
                maskedEmail: app(PasswordResetCodeService::class)->maskEmail($user->email),
                expiresAt: $result['expires_at'],
            ));
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'sent']);
        }

        return back()->with('status', __('passwords.sent'));
    }
}
