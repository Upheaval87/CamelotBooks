<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxAdjustment;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxPeriod;
use App\Models\TaxPayment;
use App\Models\TaxReturn;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected TaxType $vat;
    protected TaxJurisdiction $jurisdiction;
    protected TaxCode $code;
    protected TaxPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TAXWF',
            'name' => 'Tax Workflow Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        $this->vat = TaxType::create([
            'company_id' => $this->company->id,
            'code' => 'VAT',
            'name' => 'Value Added Tax',
            'category' => 'VAT',
        ]);

        $this->jurisdiction = TaxJurisdiction::create([
            'company_id' => $this->company->id,
            'code' => 'MWI',
            'name' => 'Malawi',
            'country' => 'MW',
            'authority' => 'Malawi Revenue Authority',
            'active' => true,
        ]);

        $this->code = TaxCode::create([
            'company_id' => $this->company->id,
            'code' => 'VAT_STD',
            'name' => 'Standard VAT',
            'tax_type_id' => $this->vat->id,
            'jurisdiction_id' => $this->jurisdiction->id,
            'treatment' => 'INCLUSIVE',
            'effective_from' => '2024-01-01',
            'is_active' => true,
        ]);

        TaxCodeRate::create([
            'tax_code_id' => $this->code->id,
            'rate_pct' => 16.5,
            'effective_from' => '2024-01-01',
            'effective_to' => null,
        ]);

        $this->period = TaxPeriod::create([
            'company_id' => $this->company->id,
            'tax_type_id' => $this->vat->id,
            'label' => 'Jul 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'filing_due_date' => '2026-08-25',
            'status' => 'OPEN',
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '2300',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
    }

    public function test_approve_adjustment(): void
    {
        $adj = TaxAdjustment::create([
            'company_id' => $this->company->id,
            'period_id' => $this->period->id,
            'tax_type_id' => $this->vat->id,
            'amount' => 500,
            'direction' => 'ADD',
            'reason' => 'Correction',
            'status' => 'PENDING',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.taxation.adjustments.approve', $adj->id))
            ->assertRedirect();

        $adj->refresh();
        $this->assertEquals('APPROVED', $adj->status);
        $this->assertEquals($this->user->id, $adj->approved_by);
    }

    public function test_reject_adjustment(): void
    {
        $adj = TaxAdjustment::create([
            'company_id' => $this->company->id,
            'period_id' => $this->period->id,
            'tax_type_id' => $this->vat->id,
            'amount' => 500,
            'direction' => 'ADD',
            'reason' => 'Correction',
            'status' => 'PENDING',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.taxation.adjustments.reject', $adj->id), ['reason' => 'Invalid'])
            ->assertRedirect();

        $adj->refresh();
        $this->assertEquals('REJECTED', $adj->status);
    }

    public function test_cannot_approve_non_pending_adjustment(): void
    {
        $adj = TaxAdjustment::create([
            'company_id' => $this->company->id,
            'period_id' => $this->period->id,
            'tax_type_id' => $this->vat->id,
            'amount' => 500,
            'direction' => 'ADD',
            'reason' => 'Already approved',
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.taxation.adjustments.approve', $adj->id))
            ->assertStatus(422);
    }

    public function test_close_period(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.taxation.periods.close', $this->period->id))
            ->assertRedirect();

        $this->period->refresh();
        $this->assertEquals('CLOSED', $this->period->status);
    }

    public function test_cannot_close_already_closed_period(): void
    {
        $this->period->update(['status' => 'CLOSED']);

        $this->actingAs($this->user)
            ->post(route('accounting.taxation.periods.close', $this->period->id))
            ->assertStatus(422);
    }

    public function test_void_payment(): void
    {
        $payment = TaxPayment::create([
            'company_id' => $this->company->id,
            'tax_type_id' => $this->vat->id,
            'period_id' => $this->period->id,
            'amount' => 1000,
            'payment_date' => '2026-07-15',
            'status' => 'CONFIRMED',
            'payment_ref' => 'TXP-001',
            'recorded_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.taxation.payments.void', $payment->id))
            ->assertRedirect();

        $payment->refresh();
        $this->assertEquals('voided', $payment->status);
    }

    public function test_audit_trail_logged_on_approve(): void
    {
        $adj = TaxAdjustment::create([
            'company_id' => $this->company->id,
            'period_id' => $this->period->id,
            'tax_type_id' => $this->vat->id,
            'amount' => 500,
            'direction' => 'ADD',
            'reason' => 'Audit test',
            'status' => 'PENDING',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.taxation.adjustments.approve', $adj->id));

        $log = \App\Models\TaxAuditTrail::where('entity_id', $adj->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('TAX_ADJUSTMENT', $log->entity_kind);
        $this->assertEquals('APPROVED', $log->new_value);
    }

    public function test_audit_trail_logged_on_close_period(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.taxation.periods.close', $this->period->id));

        $log = \App\Models\TaxAuditTrail::where('entity_id', $this->period->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('TAX_PERIOD', $log->entity_kind);
        $this->assertEquals('CLOSED', $log->new_value);
    }
}
