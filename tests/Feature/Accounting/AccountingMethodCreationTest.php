<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 — Accounting Method step on the two company-creation surfaces.
 *
 * The spec (§3) says the method is chosen ONCE at company creation and made a
 * single option-card step ("Accrual" pre-selected). Because neither creation
 * surface is a 4-step wizard (discovery confirmed: self-serve is a create
 * modal, Super Admin is a single full-page form), the step is added as an
 * in-form section rendered by the SHARED `components/accounting/method-picker`
 * partial, and both store handlers persist accounting_method /
 * reporting_preference.
 */
class AccountingMethodCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    public function test_self_serve_creation_modal_renders_the_method_picker_with_accrual_default(): void
    {
        $this->actingAs($this->makeSuperAdmin());

        $this->get(route('companies.index'))
            ->assertOk()
            ->assertSee('Accounting Method')
            ->assertSee('optcards', false)
            ->assertSee('value="accrual"', false)
            ->assertSee('value="cash"', false)
            ->assertSee('name="accounting_method"', false)
            ->assertSee('Accrual (Recommended)')
            ->assertSee('reporting_preference', false);
    }

    public function test_super_admin_create_form_renders_the_method_picker(): void
    {
        $this->actingAs($this->makeSuperAdmin());

        $this->get(route('superadmin.companies.create'))
            ->assertOk()
            ->assertSee('Accounting Method')
            ->assertSee('optcards', false)
            ->assertSee('What changes in your books', false)
            ->assertSee('Reporting preference', false);
    }

    public function test_super_admin_store_persists_method_and_default_reporting_preference(): void
    {
        $this->mock(\App\Services\Tenancy\CompanyProvisioningService::class, function ($mock) {
            $mock->shouldReceive('provision')->once();
        });

        $this->actingAs($this->makeSuperAdmin())
            ->post(route('superadmin.companies.store'), [
                'name' => 'Omega Ltd',
                'company_code' => 'OMGA',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'branch_limit' => 2,
                'accounting_method' => 'cash',
            ]);

        $company = Company::where('company_code', 'OMGA')->firstOrFail();
        $this->assertSame(Company::METHOD_CASH, $company->accounting_method);
        $this->assertSame(Company::REPORTING_ACCRUAL_VIEW, $company->reporting_preference);
    }

    public function test_super_admin_store_rejects_an_invalid_accounting_method(): void
    {
        $this->actingAs($this->makeSuperAdmin())
            ->post(route('superadmin.companies.store'), [
                'name' => 'Omega Ltd',
                'company_code' => 'OMGA',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'branch_limit' => 2,
                'accounting_method' => 'hybrid',
            ])
            ->assertSessionHasErrors('accounting_method');

        $this->assertDatabaseMissing('companies', ['company_code' => 'OMGA']);
    }

    public function test_self_serve_store_rejects_an_invalid_accounting_method(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('companies.store'), [
                'name' => 'Omega Ltd',
                'company_code' => 'OMGA',
                'base_currency' => 'USD',
                'fiscal_year_start_month' => 1,
                'branch_limit' => 2,
                'accounting_method' => 'hybrid',
            ])
            ->assertSessionHasErrors('accounting_method');

        $this->assertDatabaseMissing('companies', ['company_code' => 'OMGA']);
    }
}
