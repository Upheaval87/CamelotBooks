<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxAuditTrail;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxExemption;
use App\Models\TaxJurisdiction;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxConfigCreateTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TAXCRT',
            'name' => 'Tax Create Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);
    }

    protected function seedTaxType(string $code = 'VAT', string $category = 'VAT'): TaxType
    {
        return TaxType::create([
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => "$code Tax",
            'category' => $category,
            'active' => true,
        ]);
    }

    protected function seedAccount(): Account
    {
        return Account::create([
            'company_id' => $this->company->id,
            'code' => '2300',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
    }

    public function test_config_page_renders_create_buttons_for_creators(): void
    {
        $this->actingAs($this->user);

        $this->get(route('accounting.taxation.config', ['tab' => 'types']))
            ->assertStatus(200)
            ->assertSee('New Tax Type');

        $this->get(route('accounting.taxation.config', ['tab' => 'codes']))
            ->assertStatus(200)
            ->assertSee('New Tax Code');

        $this->get(route('accounting.taxation.config', ['tab' => 'rates']))
            ->assertStatus(200)
            ->assertSee('New Rate');

        $this->get(route('accounting.taxation.config', ['tab' => 'exemptions']))
            ->assertStatus(200)
            ->assertSee('New Exemption');

        $this->get(route('accounting.taxation.config', ['tab' => 'jurisdictions']))
            ->assertStatus(200)
            ->assertSee('New Jurisdiction');
    }

    public function test_create_tax_type_persists_and_logs_audit(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('accounting.taxation.types.store'), [
            'code' => 'fbt',
            'name' => 'Fringe Benefits Tax',
            'category' => 'FBT',
            'active' => 1,
        ]);

        $response->assertRedirect(route('accounting.taxation.config', ['tab' => 'types']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tax_types', [
            'company_id' => $this->company->id,
            'code' => 'FBT',
            'name' => 'Fringe Benefits Tax',
            'category' => 'FBT',
            'active' => 1,
        ]);

        $this->assertDatabaseHas('tax_audit_trail', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_kind' => 'tax_type',
            'field' => 'status',
            'new_value' => 'ACTIVE',
        ]);
    }

    public function test_create_jurisdiction_persists(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('accounting.taxation.jurisdictions.store'), [
            'code' => 'zmw',
            'name' => 'Zambia',
            'country' => 'ZM',
            'authority' => 'Zambia Revenue Authority',
            'active' => 1,
        ]);

        $response->assertRedirect(route('accounting.taxation.config', ['tab' => 'jurisdictions']));

        $this->assertDatabaseHas('tax_jurisdictions', [
            'company_id' => $this->company->id,
            'code' => 'ZMW',
            'country' => 'ZM',
        ]);
    }

    public function test_create_tax_code_with_fk_dependencies(): void
    {
        $this->actingAs($this->user);

        $type = $this->seedTaxType('VAT', 'VAT');
        $jurisdiction = TaxJurisdiction::create([
            'company_id' => $this->company->id,
            'code' => 'MWI',
            'name' => 'Malawi',
            'country' => 'MW',
            'authority' => 'MRA',
            'active' => true,
        ]);
        $account = $this->seedAccount();

        $response = $this->post(route('accounting.taxation.codes.store'), [
            'code' => 'vat_std',
            'name' => 'Standard VAT',
            'tax_type_id' => $type->id,
            'jurisdiction_id' => $jurisdiction->id,
            'treatment' => 'STANDARD',
            'price_basis' => 'EXCLUSIVE',
            'rounding_mode' => 'HALF_UP',
            'rounding_level' => 'LINE',
            'gl_output_acct' => $account->id,
            'gl_input_acct' => '',
            'gl_payable_acct' => '',
            'effective_from' => '2026-01-01',
            'active' => 1,
        ]);

        $response->assertRedirect(route('accounting.taxation.config', ['tab' => 'codes']));

        $this->assertDatabaseHas('tax_codes', [
            'company_id' => $this->company->id,
            'code' => 'VAT_STD',
            'tax_type_id' => $type->id,
            'jurisdiction_id' => $jurisdiction->id,
            'gl_output_acct' => $account->id,
            'gl_input_acct' => null,
        ]);
    }

    public function test_create_code_rejects_cross_company_tax_type(): void
    {
        $this->actingAs($this->user);

        $otherCompany = Company::create([
            'company_code' => 'OTHER',
            'name' => 'Other Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $foreignType = TaxType::create([
            'company_id' => $otherCompany->id,
            'code' => 'VAT',
            'name' => 'Foreign VAT',
            'category' => 'VAT',
        ]);

        $this->post(route('accounting.taxation.codes.store'), [
            'code' => 'CROSS',
            'name' => 'Cross',
            'tax_type_id' => $foreignType->id,
            'treatment' => 'STANDARD',
            'price_basis' => 'EXCLUSIVE',
            'rounding_mode' => 'HALF_UP',
            'rounding_level' => 'LINE',
            'effective_from' => '2026-01-01',
        ])->assertStatus(404);

        $this->assertDatabaseMissing('tax_codes', ['code' => 'CROSS']);
    }

    public function test_create_rate_persists(): void
    {
        $this->actingAs($this->user);

        $type = $this->seedTaxType('VAT', 'VAT');
        $code = TaxCode::create([
            'company_id' => $this->company->id,
            'code' => 'VAT_STD',
            'name' => 'Standard VAT',
            'tax_type_id' => $type->id,
            'treatment' => 'STANDARD',
            'price_basis' => 'EXCLUSIVE',
            'effective_from' => '2026-01-01',
            'active' => true,
        ]);

        $this->post(route('accounting.taxation.rates.store'), [
            'tax_code_id' => $code->id,
            'rate_pct' => 16.5,
            'effective_from' => '2026-01-01',
        ])->assertRedirect(route('accounting.taxation.config', ['tab' => 'rates']));

        $this->assertDatabaseHas('tax_code_rates', [
            'tax_code_id' => $code->id,
            'rate_pct' => 16.5,
        ]);
    }

    public function test_create_exemption_persists(): void
    {
        $this->actingAs($this->user);

        $type = $this->seedTaxType('VAT', 'VAT');

        $this->post(route('accounting.taxation.exemptions.store'), [
            'code' => 'exp_agri',
            'name' => 'Agricultural Inputs',
            'reason' => 'Statutory exemption',
            'scope' => 'SALES',
            'tax_type_id' => $type->id,
            'effective_from' => '2026-01-01',
            'active' => 1,
        ])->assertRedirect(route('accounting.taxation.config', ['tab' => 'exemptions']));

        $this->assertDatabaseHas('tax_exemptions', [
            'company_id' => $this->company->id,
            'code' => 'EXP_AGRI',
            'scope' => 'SALES',
            'tax_type_id' => $type->id,
        ]);
    }

    public function test_create_validates_category(): void
    {
        $this->actingAs($this->user);

        $this->post(route('accounting.taxation.types.store'), [
            'code' => 'BAD',
            'name' => 'Bad',
            'category' => 'NONSENSE',
        ])->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('tax_types', ['code' => 'BAD']);
    }

    public function test_tax_audit_trail_records_type_creation(): void
    {
        $this->actingAs($this->user);

        $this->post(route('accounting.taxation.types.store'), [
            'code' => 'paye',
            'name' => 'PAYE',
            'category' => 'PAYE',
            'active' => 1,
        ]);

        $log = TaxAuditTrail::where('company_id', $this->company->id)
            ->where('entity_kind', 'tax_type')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->user->id, (int) $log->user_id);
        $this->assertSame('ACTIVE', (string) $log->new_value);
    }
}