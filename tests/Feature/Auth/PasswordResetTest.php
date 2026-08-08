<?php

namespace Tests\Feature\Auth;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200)
            ->assertSee('Reset access', false)
            ->assertSee('Forgot your password?', false)
            ->assertSee('Send reset link', false)
            ->assertSee('Links expire after 30 minutes', false)
            ->assertSee('Back to sign in', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_reset_code_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('password_reset_email', $user->email)
            ->assertRedirect(route('password.verify-code'));

        Mail::assertQueued(VerificationCodeMail::class, 1);
    }

    public function test_reset_request_for_unknown_email_shows_inline_error(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('password_reset_email');

        Mail::assertNothingQueued();
    }

    public function test_reset_request_json_rejects_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Mail::assertNothingQueued();
    }

    public function test_verify_code_page_renders_masked_email_and_expiry(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'jane.doe@example.com']);

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->get('/verify-code')
            ->assertStatus(200)
            ->assertSee('Verification', false)
            ->assertSee('Enter verification code', false)
            ->assertSee('j***@example.com', false)
            ->assertSee('Codes expire after 5 minutes', false)
            ->assertSee('Digit 1 of 6', false)
            ->assertSee('Digit 6 of 6', false)
            ->assertSee('Verify &amp; continue', false)
            ->assertSee('Back to sign in', false)
            ->assertSee('Resend', false)
            ->assertSee('Didn\'t receive it');
    }

    public function test_verify_code_page_redirects_to_forgot_without_session_email(): void
    {
        $this->get('/verify-code')
            ->assertRedirect(route('password.request'));
    }

    public function test_verify_code_page_redirects_to_forgot_without_pending_code(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        VerificationCode::query()->update(['used_at' => now()]);

        $this->get('/verify-code')
            ->assertRedirect(route('password.request'));
    }

    public function test_invalid_code_returns_generic_error(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->postJson('/verify-code', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_expired_code_is_rejected_even_when_correct(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $code = Mail::queued(VerificationCodeMail::class)->first()->code;

        VerificationCode::query()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/verify-code', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_resend_is_blocked_within_cooldown(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->postJson('/resend-code')
            ->assertStatus(429)
            ->assertJsonPath('message', 'Please wait a moment before requesting another code.')
            ->assertJsonStructure(['retry_after']);
    }

    public function test_resend_issues_new_code_and_invalidates_old(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        VerificationCode::query()->update(['created_at' => now()->subMinutes(2)]);

        $this->postJson('/resend-code')
            ->assertOk()
            ->assertJsonStructure(['expires_at', 'resend_after']);

        $queued = Mail::queued(VerificationCodeMail::class);
        $this->assertCount(2, $queued);

        $oldCode = $queued[0]->code;
        $newCode = $queued[1]->code;
        $this->assertNotEquals($oldCode, $newCode);

        $this->postJson('/verify-code', ['code' => $oldCode])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->postJson('/verify-code', ['code' => $newCode])
            ->assertOk()
            ->assertJsonStructure(['redirect']);
    }

    public function test_cancel_forgets_the_stored_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('password_reset_email');

        $this->postJson('/verify-code/cancel')->assertOk()
            ->assertSessionMissing('password_reset_email');

        $this->get('/verify-code')->assertRedirect(route('password.request'));
    }

    public function test_password_can_be_reset_after_code_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $code = Mail::queued(VerificationCodeMail::class)->first()->code;
        $this->assertNotNull($code);

        $response = $this->postJson('/verify-code', ['code' => $code]);
        $response->assertOk()->assertJsonStructure(['redirect']);

        $resetUrl = $response->json('redirect');
        $this->assertStringStartsWith('/reset-password/', $resetUrl);

        $token = basename((string) parse_url($resetUrl, PHP_URL_PATH));

        parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);
        $this->assertEquals($user->email, $query['email']);

        $this->get($resetUrl)->assertStatus(200);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-password1',
            'password_confirmation' => 'New-password1',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New-password1', $user->fresh()->password));
    }

    public function test_reset_password_page_shows_requirements_checklist(): void
    {
        $user = User::factory()->create();

        $this->get('/reset-password/token-placeholder?email=' . $user->email)
            ->assertStatus(200)
            ->assertSee('Secure reset', false)
            ->assertSee('Set a new password', false)
            ->assertSee('Choose a strong password', false)
            ->assertSee('At least 8 characters', false)
            ->assertSee('One uppercase letter (A–Z)', false)
            ->assertSee('One lowercase letter (a–z)', false)
            ->assertSee('One number (0–9)', false)
            ->assertSee('auth-login-password-checklist', false)
            ->assertSee('newPassword(', false)
            ->assertSee('Set new password', false)
            ->assertSee('You\'ll be signed out of all other devices')
            ->assertSee('Back to sign in', false);
    }

    public function test_invalid_reset_token_redirects_back_to_forgot_password(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'New-password1',
            'password_confirmation' => 'New-password1',
        ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
