<?php

namespace Tests\Feature\POS;

use App\Models\Company;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCashierAuthTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'POS Auth Co',
            'company_code' => 'PAC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'pos_cashier_pin' => '1234',
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'accountant']);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Front Counter',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        session(['current_company_id' => $this->company->id]);
        FeatureManagement::enable($this->company->id, 'pos');
    }

    private function loginAsUser(): void
    {
        $this->actingAs($this->user);
    }

    // =============================================
    // PIN LOGIN FORM
    // =============================================

    public function test_pin_login_form_loads(): void
    {
        $this->loginAsUser();
        $this->get(route('pos.cashier.login'))->assertOk();
    }

    public function test_pin_login_form_shows_active_terminals(): void
    {
        $this->loginAsUser();
        $response = $this->get(route('pos.cashier.login'));
        $response->assertOk();
        $response->assertSee('Front Counter');
        $response->assertSee('T1');
    }

    // =============================================
    // PIN LOGIN
    // =============================================

    public function test_valid_pin_logs_in_cashier(): void
    {
        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '1234',
            'terminal_id' => $this->terminal->id,
        ])->assertRedirect(route('pos.dashboard'));

        $this->assertEquals($this->user->id, session('pos_cashier_id'));
        $this->assertEquals($this->terminal->id, session('pos_terminal_id'));
        $this->assertEquals('T1', session('pos_terminal_identifier'));
        $this->assertEquals($this->user->name, session('pos_cashier_name'));
    }

    public function test_invalid_pin_fails(): void
    {
        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '9999',
            'terminal_id' => $this->terminal->id,
        ])->assertSessionHasErrors('pin');
    }

    public function test_missing_pin_fails(): void
    {
        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'terminal_id' => $this->terminal->id,
        ])->assertSessionHasErrors('pin');
    }

    public function test_inactive_terminal_fails(): void
    {
        $this->terminal->update(['is_active' => false]);

        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '1234',
            'terminal_id' => $this->terminal->id,
        ])->assertSessionHasErrors('terminal_id');
    }

    public function test_wrong_company_terminal_fails(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $otherTerminal = PosTerminal::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Terminal',
            'identifier' => 'OT1',
            'is_active' => true,
        ]);

        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '1234',
            'terminal_id' => $otherTerminal->id,
        ])->assertSessionHasErrors('terminal_id');
    }

    public function test_user_without_access_fails(): void
    {
        $outsider = User::factory()->create([
            'pos_cashier_pin' => '5678',
        ]);

        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '5678',
            'terminal_id' => $this->terminal->id,
        ])->assertSessionHasErrors('pin');
    }

    public function test_pin_without_value_fails(): void
    {
        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '0000',
            'terminal_id' => $this->terminal->id,
        ])->assertSessionHasErrors('pin');
    }

    // =============================================
    // PIN LOGOUT
    // =============================================

    public function test_cashier_logout_clears_session(): void
    {
        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '1234',
            'terminal_id' => $this->terminal->id,
        ]);

        $this->assertNotNull(session('pos_cashier_id'));

        $this->post(route('pos.cashier.logout'))->assertRedirect(route('pos.cashier.login'));
        $this->assertNull(session('pos_cashier_id'));
        $this->assertNull(session('pos_terminal_id'));
    }

    // =============================================
    // MIDDLEWARE PROTECTION
    // =============================================

    public function test_pos_dashboard_requires_cashier_pin(): void
    {
        $this->loginAsUser();
        $this->get(route('pos.dashboard'))->assertRedirect(route('pos.cashier.login'));
    }

    public function test_pos_dashboard_accessible_after_pin_login(): void
    {
        $this->loginAsUser();
        $this->post(route('pos.cashier.login.post'), [
            'pin' => '1234',
            'terminal_id' => $this->terminal->id,
        ]);

        $this->get(route('pos.dashboard'))->assertOk();
    }
}
