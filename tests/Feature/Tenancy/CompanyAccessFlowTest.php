<?php

namespace Tests\Feature\Tenancy;

use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyAccessLog;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(string $code = 'ACME', bool $provisioned = true): Company
    {
        return Company::create([
            'company_code' => $code,
            'name' => $code . ' Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => $provisioned ? Company::STATUS_ACTIVE : Company::STATUS_PENDING,
            'db_name' => $provisioned ? 'acct_' . strtolower($code) . '_00000001' : null,
            'provisioned_at' => $provisioned ? now() : null,
        ]);
    }

    private function assign(User $user, Company $company, string $role = 'company_admin', bool $active = true): UserCompanyAssignment
    {
        return UserCompanyAssignment::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function resolver(): TenantConnectionResolver
    {
        return app(TenantConnectionResolver::class);
    }

    private function login(User $user)
    {
        return $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
    }

    public function test_login_auto_selects_the_single_assignment(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $this->assign($user, $company);

        $response = $this->login($user);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($company->id, session('current_company_id'));
        $this->assertTrue($this->resolver()->isBound());

        $log = CompanyAccessLog::where('user_id', $user->id)->where('company_id', $company->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(CompanyAccessLog::ACTION_LOGIN, $log->action);
    }

    public function test_login_with_multiple_assignments_lands_on_the_picker(): void
    {
        $user = User::factory()->create();
        $this->assign($user, $this->makeCompany('ACME'));
        $this->assign($user, $this->makeCompany('BETA'));

        $response = $this->login($user);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('companies.index', absolute: false));
        $this->assertNull(session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_login_without_assignments_lands_on_the_picker(): void
    {
        $user = User::factory()->create();

        $response = $this->login($user);

        $response->assertRedirect(route('companies.index', absolute: false));
        $this->assertNull(session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_super_admin_login_lands_on_the_panel_without_binding(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $this->assign($user, $this->makeCompany('ACME'));

        $response = $this->login($user);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('panel.dashboard', absolute: false));
        $this->assertNull(session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
        $this->assertDatabaseCount('company_access_logs', 0);
    }

    public function test_super_admin_can_enter_a_company_explicitly_and_is_logged(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $company = $this->makeCompany();
        $this->assign($user, $company);

        $response = $this->actingAs($user)->post(route('companies.select', $company->id));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($company->id, session('current_company_id'));
        $this->assertTrue($this->resolver()->isBound());

        $log = CompanyAccessLog::where('user_id', $user->id)->where('company_id', $company->id)->first();
        $this->assertNotNull($log);
        $this->assertSame(CompanyAccessLog::ACTION_SUPPORT, $log->action);
    }

    public function test_select_rejects_a_forged_company_id(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany('ACME');
        $other = User::factory()->create();
        $this->assign($other, $company);

        $response = $this->actingAs($user)->post(route('companies.select', $company->id));

        $response->assertNotFound();
        $this->assertNull(session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_select_rejects_a_mismatched_company_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $companyA = $this->makeCompany('ACME');
        $companyB = $this->makeCompany('BETA');
        $this->assign($user, $companyA);
        $this->assign($other, $companyB);

        $response = $this->actingAs($user)->post(route('companies.select', $companyB->id));

        $response->assertNotFound();
        $this->assertNull(session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_select_rejects_a_nonexistent_company(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('companies.select', 99999));

        $response->assertNotFound();
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_switch_company_without_re_login(): void
    {
        $user = User::factory()->create();
        $companyA = $this->makeCompany('ACME');
        $companyB = $this->makeCompany('BETA');
        $this->assign($user, $companyA);
        $this->assign($user, $companyB);

        $response = $this->login($user);
        $response->assertRedirect(route('companies.index', absolute: false));

        $this->actingAs($user)->post(route('companies.select', $companyA->id))
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($companyA->id, session('current_company_id'));
        $this->assertTrue($this->resolver()->isBound());

        $this->actingAs($user)->post(route('companies.select', $companyB->id))
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($companyB->id, session('current_company_id'));
        $this->assertTrue($this->resolver()->isBound());

        $this->assertDatabaseHas('company_access_logs', [
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'action' => CompanyAccessLog::ACTION_SELECT,
        ]);
        $this->assertDatabaseHas('company_access_logs', [
            'user_id' => $user->id,
            'company_id' => $companyB->id,
            'action' => CompanyAccessLog::ACTION_SELECT,
        ]);
    }

    public function test_select_allows_legacy_unprovisioned_company_without_binding(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany('ACME', provisioned: false);
        $this->assign($user, $company);

        $response = $this->actingAs($user)->post(route('companies.select', $company->id));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame($company->id, session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
        $this->assertDatabaseHas('company_access_logs', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'action' => CompanyAccessLog::ACTION_SELECT,
        ]);
    }

    public function test_middleware_rejects_after_assignment_is_deactivated(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $assignment = $this->assign($user, $company);

        $this->login($user);
        $this->assertSame($company->id, session('current_company_id'));

        $assignment->update(['is_active' => false]);

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('companies.index', absolute: false));
        $this->assertNull(session('current_company_id'));
    }

    public function test_middleware_allows_legacy_unprovisioned_session_company_without_binding(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany('ACME', provisioned: false);
        $this->assign($user, $company);

        $this->actingAs($user)->withSession(['current_company_id' => $company->id])
            ->get('/dashboard')
            ->assertStatus(200);

        $this->assertSame($company->id, session('current_company_id'));
        $this->assertFalse($this->resolver()->isBound());
    }

    public function test_tenant_models_resolve_to_the_override_connection_while_bound(): void
    {
        $user = User::factory()->create();
        $company = $this->makeCompany();
        $this->assign($user, $company);

        $this->actingAs($user)->post(route('companies.select', $company->id))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame('sqlite', (new Account())->getConnectionName());

        Account::create([
            'company_id' => $company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->assertSame(1, Account::where('company_id', $company->id)->count());
        $this->assertSame('Cash', Account::where('company_id', $company->id)->first()->name);
    }
}
