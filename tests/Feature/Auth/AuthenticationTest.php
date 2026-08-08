<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_renders_split_panel_and_form(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('auth-brand', false)
            ->assertSee('auth-form-col', false)
            ->assertSee('Welcome back', false)
            ->assertSee('name="remember"', false)
            ->assertSee('auth-login-forgot', false)
            ->assertSee('Log in', false)
            ->assertSee('Contact your administrator', false);
    }

    public function test_login_screen_password_toggle_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('aria-pressed', false)
            ->assertSee('Show password', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('companies.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
