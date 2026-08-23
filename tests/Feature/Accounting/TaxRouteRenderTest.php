<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxType;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxRouteRenderTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TAXTST',
            'name' => 'Tax Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        // Seed tax types + codes + rates + jurisdiction
        $vat = TaxType::create([
            'company_id' => $this->company->id,
            'code' => 'VAT',
            'name' => 'Value Added Tax',
            'category' => 'VAT',
        ]);
        $wht = TaxType::create([
            'company_id' => $this->company->id,
            'code' => 'WHT',
            'name' => 'Withholding Tax',
            'category' => 'WHT',
        ]);

        $jurisdiction = TaxJurisdiction::create([
            'company_id' => $this->company->id,
            'code' => 'MWI',
            'name' => 'Malawi',
            'country' => 'MW',
            'authority' => 'Malawi Revenue Authority',
            'active' => true,
        ]);

        $code1 = TaxCode::create([
            'company_id' => $this->company->id,
            'code' => 'VAT_STD',
            'name' => 'Standard VAT',
            'tax_type_id' => $vat->id,
            'jurisdiction_id' => $jurisdiction->id,
            'treatment' => 'INCLUSIVE',
            'effective_from' => '2024-01-01',
            'is_active' => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id' => $code1->id,
            'rate_pct' => 16.5,
            'effective_from' => '2024-01-01',
            'effective_to' => null,
        ]);

        $code2 = TaxCode::create([
            'company_id' => $this->company->id,
            'code' => 'WHT_SUP',
            'name' => 'Supplier WHT',
            'tax_type_id' => $wht->id,
            'jurisdiction_id' => $jurisdiction->id,
            'treatment' => 'DEDUCTED',
            'effective_from' => '2024-01-01',
            'is_active' => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id' => $code2->id,
            'rate_pct' => 10.0,
            'effective_from' => '2024-01-01',
            'effective_to' => null,
        ]);

        // Default tax accounts
        Account::create([
            'company_id' => $this->company->id,
            'code' => '2300',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
        Account::create([
            'company_id' => $this->company->id,
            'code' => '1150',
            'name' => 'Tax Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);
    }

    public function test_all_18_tax_routes_render(): void
    {
        $this->actingAs($this->user);

        $routes = [
            'dashboard'         => route('accounting.taxation.dashboard'),
            'config'            => route('accounting.taxation.config'),
            'config-types'      => route('accounting.taxation.config', ['tab' => 'types']),
            'config-rates'      => route('accounting.taxation.config', ['tab' => 'rates']),
            'config-codes'      => route('accounting.taxation.config', ['tab' => 'codes']),
            'config-exemptions' => route('accounting.taxation.config', ['tab' => 'exemptions']),
            'config-jurisdictions' => route('accounting.taxation.config', ['tab' => 'jurisdictions']),
            'config-accounts'   => route('accounting.taxation.config', ['tab' => 'accounts']),
            'codes'             => route('accounting.taxation.codes'),
            'types'             => route('accounting.taxation.types'),
            'rates'             => route('accounting.taxation.rates'),
            'exemptions'        => route('accounting.taxation.exemptions'),
            'jurisdictions'     => route('accounting.taxation.jurisdictions'),
            'accounts'          => route('accounting.taxation.accounts'),
            'periods'           => route('accounting.taxation.periods'),
            'reconciliation'    => route('accounting.taxation.reconciliation'),
            'certificates'      => route('accounting.taxation.certificates'),
            'reports'           => route('accounting.taxation.reports'),
            'audit-trail'       => route('accounting.taxation.audit-trail'),
            'position'          => route('accounting.taxation.position'),
            'control-account'   => route('accounting.taxation.control-account'),
            'payments'          => route('accounting.taxation.payments'),
            'recognition-rules' => route('accounting.taxation.recognition-rules'),
        ];

        $this->assertCount(23, $routes);

        foreach ($routes as $name => $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
        }
    }

    public function test_working_paper_renders(): void
    {
        $this->actingAs($this->user);

        $period = \App\Models\TaxPeriod::create([
            'company_id' => $this->company->id,
            'tax_type_id' => TaxType::where('code', 'VAT')->first()->id,
            'label' => 'Jul 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'filing_due_date' => '2026-08-25',
            'status' => 'OPEN',
        ]);

        $url = route('accounting.taxation.returns.working-paper', ['periodId' => $period->id]);
        $response = $this->get($url);
        $response->assertStatus(200);
    }
}
