<?php

namespace Tests\Feature\POS;

use App\Models\Company;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $admin;
    protected PosTerminal $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Pos Auth Test Co',
            'company_code' => 'PAT',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->setPosPin('1234');
        $this->admin->refresh();
        $this->admin->companies()->attach($this->company->id, ['role' => 'company_admin']);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Main Terminal',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        session(['current_company_id' => $this->company->id]);
        FeatureManagement::enable($this->company->id, 'pos');
        $this->actingAs($this->admin);
    }

    // =============================================
    // PIN HASHING
    // =============================================

    public function test_pin_is_stored_hashed(): void
    {
        $this->assertNotEquals('1234', $this->admin->pos_cashier_pin);
        $this->assertTrue(str_starts_with($this->admin->pos_cashier_pin, '$2y$'));
        $this->assertTrue($this->admin->verifyPosPin('1234'));
        $this->assertFalse($this->admin->verifyPosPin('0000'));
    }

    public function test_has_pos_pin(): void
    {
        $this->assertTrue($this->admin->hasPosPin());

        $userWithout = User::factory()->create();
        $this->assertFalse($userWithout->hasPosPin());
    }

    public function test_pin_hidden_from_to_array(): void
    {
        $array = $this->admin->toArray();
        $this->assertArrayNotHasKey('pos_cashier_pin', $array);
    }

    // =============================================
    // LOGIN PAGE
    // =============================================

    public function test_login_page_renders(): void
    {
        $response = $this->get(route('pos.login'));
        $response->assertStatus(200);
        $response->assertSee('Sign in to POS');
        $response->assertSee('pos-auth-split');
        $response->assertSee('pos-auth-body');
        $response->assertSee('Password');
        $response->assertSee('Cashier PIN');
    }

    public function test_login_page_shows_cashier_chips(): void
    {
        $response = $this->get(route('pos.login'));
        $response->assertSee($this->admin->name);
    }

    public function test_login_page_shows_terminals(): void
    {
        $response = $this->get(route('pos.login'));
        $response->assertSee('T1');
    }

    // =============================================
    // PIN LOGIN (via new pos.login route)
    // =============================================

    public function test_pin_login_succeeds(): void
    {
        $response = $this->post(route('pos.login.post'), [
            'auth_type' => 'pin',
            'pin' => '1234',
            'terminal_id' => $this->terminal->id,
        ]);

        $response->assertRedirect(route('pos.dashboard'));
        $response->assertSessionHas('pos_cashier_id', $this->admin->id);
        $response->assertSessionHas('pos_terminal_id', $this->terminal->id);
        $response->assertSessionHas('pos_terminal_identifier', 'T1');
        $response->assertSessionHas('pos_cashier_name', $this->admin->name);
    }

    public function test_pin_login_fails_with_wrong_pin(): void
    {
        $response = $this->post(route('pos.login.post'), [
            'auth_type' => 'pin',
            'pin' => '9999',
            'terminal_id' => $this->terminal->id,
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertNull(session('pos_cashier_id'));
    }

    // =============================================
    // PASSWORD LOGIN (via new pos.login route)
    // =============================================

    public function test_password_login_succeeds(): void
    {
        $response = $this->post(route('pos.login.post'), [
            'auth_type' => 'password',
            'email' => $this->admin->email,
            'password' => 'password',
            'terminal_id' => $this->terminal->id,
        ]);

        $response->assertRedirect(route('pos.dashboard'));
        $response->assertSessionHas('pos_cashier_id', $this->admin->id);
    }

    public function test_password_login_fails_with_wrong_password(): void
    {
        $response = $this->post(route('pos.login.post'), [
            'auth_type' => 'password',
            'email' => $this->admin->email,
            'password' => 'wrong-password',
            'terminal_id' => $this->terminal->id,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('pos_cashier_id'));
    }

    public function test_password_login_fails_with_inactive_terminal(): void
    {
        $this->terminal->update(['is_active' => false]);

        $response = $this->post(route('pos.login.post'), [
            'auth_type' => 'password',
            'email' => $this->admin->email,
            'password' => 'password',
            'terminal_id' => $this->terminal->id,
        ]);

        $response->assertSessionHasErrors('terminal_id');
    }

    // =============================================
    // LOGOUT
    // =============================================

    public function test_logout_clears_pos_session(): void
    {
        $this->session([
            'pos_cashier_id' => $this->admin->id,
            'pos_terminal_id' => $this->terminal->id,
            'pos_cashier_name' => $this->admin->name,
        ]);

        $response = $this->post(route('pos.logout'));
        $response->assertRedirect(route('pos.login'));
        $this->assertNull(session('pos_cashier_id'));
        $this->assertNull(session('pos_terminal_id'));
    }

    public function test_legacy_logout_alias_works(): void
    {
        $this->session(['pos_cashier_id' => $this->admin->id]);

        $response = $this->post(route('pos.cashier.logout'));
        $response->assertRedirect(route('pos.login'));
        $this->assertNull(session('pos_cashier_id'));
    }

    // =============================================
    // LEGACY ALIAS ROUTES
    // =============================================

    public function test_legacy_cashier_login_route(): void
    {
        $response = $this->get(route('pos.cashier.login'));
        $response->assertStatus(200);
        $response->assertSee('Sign in to POS');
    }

    public function test_legacy_cashier_login_post_works(): void
    {
        $response = $this->post(route('pos.cashier.login.post'), [
            'pin' => '1234',
            'terminal_id' => $this->terminal->id,
        ]);

        $response->assertRedirect(route('pos.dashboard'));
        $response->assertSessionHas('pos_cashier_id', $this->admin->id);
    }

    // =============================================
    // RESET PAGE
    // =============================================

    public function test_reset_page_renders(): void
    {
        $response = $this->get(route('pos.reset'));
        $response->assertStatus(200);
        $response->assertSee('Reset access');
        $response->assertSee('pos-auth-split');
    }

    // =============================================
    // VERIFY PAGE
    // =============================================

    public function test_verify_page_renders(): void
    {
        $this->session(['pos_cashier_id' => $this->admin->id]);

        $response = $this->get(route('pos.verify'));
        $response->assertStatus(200);
        $response->assertSee('Verify identity');
        $response->assertSee('pos-auth-split');
    }

    public function test_verify_pin_succeeds(): void
    {
        $this->session(['pos_cashier_id' => $this->admin->id]);

        $response = $this->post(route('pos.verify.post'), [
            'pin' => '1234',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('pos_verified_at');
    }

    public function test_verify_pin_fails_with_wrong_pin(): void
    {
        $this->session(['pos_cashier_id' => $this->admin->id]);

        $response = $this->post(route('pos.verify.post'), [
            'pin' => '0000',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertNull(session('pos_verified_at'));
    }

    public function test_verify_password_succeeds(): void
    {
        $this->session(['pos_cashier_id' => $this->admin->id]);

        $response = $this->post(route('pos.verify.password'), [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('pos_verified_at');
    }

    public function test_verify_password_fails_with_wrong_password(): void
    {
        $this->session(['pos_cashier_id' => $this->admin->id]);

        $response = $this->post(route('pos.verify.password'), [
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNull(session('pos_verified_at'));
    }
}
