<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasswordResetCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PosAuthController extends Controller
{
    public function __construct(
        protected PasswordResetCodeService $codeService,
    ) {}

    /**
     * Show the reset form (PIN or password).
     */
    public function showReset(Request $request)
    {
        return view('pos.auth.reset', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    /**
     * Handle reset request — issue verification code.
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => __("We can't find an account with that email address.")]);
        }

        $code = $this->codeService->issue($user);
        $this->codeService->sendCode($user, $code);

        $request->session()->put('password_reset_email', $user->email);

        return back()->with('status', 'Verification code sent.');
    }

    /**
     * Verify the 6-digit code and issue a reset token.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ]);

        $email = $request->session()->get('password_reset_email');

        if (!$email) {
            return redirect()->route('pos.reset');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('pos.reset');
        }

        $valid = $this->codeService->verify($user, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'That code isn\'t valid or has expired.']);
        }

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

        return redirect()->route('pos.reset.password', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    /**
     * Show the new password/PIN form.
     */
    public function showNewPassword(Request $request, string $token)
    {
        return view('pos.auth.new-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Save the new password/PIN.
     */
    public function storeNewPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('pos.reset');
        }

        $status = \Illuminate\Support\Facades\Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            fn ($user, $password) => $user->forceFill([
                'password' => Hash::make($password),
                'password_changed_at' => now(),
            ])->save()
        );

        if ($status === \Illuminate\Auth\Passwords\PasswordBroker::PASSWORD_RESET) {
            $request->session()->forget('password_reset_email');
            return redirect()->route('pos.cashier.login')
                ->with('status', 'Password reset successfully. Please sign in.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * PIN identity verification gate.
     */
    public function showVerify()
    {
        return view('pos.auth.verify');
    }

    /**
     * Verify PIN for sensitive action.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:10',
        ]);

        $userId = session('pos_cashier_id');

        if (!$userId) {
            return redirect()->route('pos.cashier.login');
        }

        $user = User::find($userId);

        if (!$user || !$user->verifyPosPin($request->pin)) {
            return back()->withErrors(['pin' => 'Invalid PIN.'])->withInput();
        }

        session(['pos_verified_at' => now()->toIso8601String()]);

        $action = session('pos_verify_action');
        session()->forget('pos_verify_action');

        return redirect()->to($action ?? route('pos.dashboard'));
    }

    /**
     * Use password instead of PIN for verification.
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $userId = session('pos_cashier_id');

        if (!$userId) {
            return redirect()->route('pos.cashier.login');
        }

        $user = User::find($userId);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Invalid password.'])->withInput();
        }

        session(['pos_verified_at' => now()->toIso8601String()]);

        $action = session('pos_verify_action');
        session()->forget('pos_verify_action');

        return redirect()->to($action ?? route('pos.dashboard'));
    }
}
