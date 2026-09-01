<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — register the previously-routeless `accounting.coa.setup` page and
 * confirm it surfaces the company's inherited accounting method (spec §4).
 *
 * coa.setup never had a route (discovery: zero "coa" in routes/, CoaController
 * not imported). It now renders on `accounting.coa.setup` and its method card:
 *   - shows the inherited method + a "Default COA" line reflecting the method
 *   - shows an admin-only "Change at company level" button linking to
 *     superadmin.companies.edit
 * The company-level change is persisted by SuperAdmin\CompaniesController::update()
 * (accounting_method + reporting_preference).
 */
class CoaSetupTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'accounting_method' => Company::METHOD_CASH,
        ]);
        $this->admin->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->admin->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        foreach (['banking', 'fixed_assets', 'inventory', 'pos', 'purchasing', 'budgets'] as $feature) {
            FeatureManagement::enable($this->company->id, $feature);
        }

        Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    public function test_coa_setup_route_is_registered_and_renders_inherited_method(): void
    {
        $this->actingAs($this->admin)
            ->get(route('accounting.coa.setup'))
            ->assertOk()
            ->assertSee('Structure Setup')
            ->assertSee('Inherited · Cash (from company)', false)
            ->assertSee('Default COA', false)
            ->assertSee('Cash template — AR/AP/inventory inactive', false);
    }

    public function test_coa_setup_default_coa_reflects_accrual_method(): void
    {
        $this->company->update(['accounting_method' => Company::METHOD_ACCRUAL]);

        $this->actingAs($this->admin)
            ->get(route('accounting.coa.setup'))
            ->assertOk()
            ->assertSee('Inherited · Accrual (from company)', false)
            ->assertSee('Accrual template — AR/AP/inventory active', false);
    }

    public function test_change_at_company_level_links_to_superadmin_edit_for_super_admin(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($super)
            ->get(route('accounting.coa.setup'))
            ->assertOk()
            ->assertSee('Change at company level', false)
            ->assertSee(route('superadmin.companies.edit', $this->company), false);
    }

    public function test_change_at_company_level_is_hidden_for_non_super_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('accounting.coa.setup'))
            ->assertOk()
            ->assertDontSee('Change at company level', false)
            ->assertSee('Inherited · Cash (from company)', false);
    }

    public function test_super_admin_company_edit_renders_accounting_method_section(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);
        $this->company->update(['reporting_preference' => Company::REPORTING_CASH_VIEW]);

        $this->actingAs($super)
            ->get(route('superadmin.companies.edit', $this->company))
            ->assertOk()
            ->assertSee('Accounting Method')
            ->assertSee('optcards', false)
            ->assertSee('value="cash"', false)
            ->assertSee('name="reporting_preference"', false)
            ->assertSee('option value="cash_view" selected', false)
            ->assertSee('option value="accrual_view"', false);
    }

    public function test_super_admin_update_persists_accounting_method_and_reporting_preference(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($super)
            ->patch(route('superadmin.companies.update', $this->company), [
                'name' => $this->company->name,
                'company_code' => $this->company->company_code,
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'accounting_method' => Company::METHOD_ACCRUAL,
                'reporting_preference' => Company::REPORTING_CASH_VIEW,
            ])
            ->assertRedirect(route('superadmin.companies.show', $this->company));

        $this->company->refresh();
        $this->assertSame(Company::METHOD_ACCRUAL, $this->company->accounting_method);
        $this->assertSame(Company::REPORTING_CASH_VIEW, $this->company->reporting_preference);
    }

    public function test_super_admin_update_rejects_an_invalid_accounting_method(): void
    {
        $super = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($super)
            ->patch(route('superadmin.companies.update', $this->company), [
                'name' => $this->company->name,
                'company_code' => $this->company->company_code,
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'accounting_method' => 'hybrid',
                'reporting_preference' => Company::REPORTING_ACCRUAL_VIEW,
            ])
            ->assertSessionHasErrors('accounting_method');

        $this->company->refresh();
        $this->assertSame(Company::METHOD_CASH, $this->company->accounting_method);
    }

    public function test_coa_setup_and_switch_accrual_are_registered_for_rails_pinning(): void
    {
        $coa = \App\Services\FavouritesService::metaForRoute('accounting.coa.setup');
        $this->assertNotNull($coa);
        $this->assertSame('coa-setup', $coa['key']);
        $this->assertSame('Chart of Accounts Structure Setup', $coa['label']);

        $switch = \App\Services\FavouritesService::metaForRoute('settings.switch_accrual');
        $this->assertNotNull($switch);
        $this->assertSame('switch-accrual', $switch['key']);
        $this->assertSame('Switch to Accrual', $switch['label']);
    }
}
