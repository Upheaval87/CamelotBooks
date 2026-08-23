<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxPeriod;
use App\Models\TaxType;
use App\Models\User;
use App\Services\Tax\TaxEngine;
use App\Services\Tax\TaxReturnService;
use App\Services\Tax\TaxPaymentService;
use App\Services\Tax\WhtCertificateService;
use App\Services\Tax\TaxRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected int $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TAXE2E',
            'name' => 'Tax E2E Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->companyId = $this->company->id;

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->companyId);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->companyId]);

        Account::create([
            'company_id' => $this->companyId,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);
        Account::create([
            'company_id' => $this->companyId,
            'code' => '1150',
            'name' => 'Tax Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);
        Account::create([
            'company_id' => $this->companyId,
            'code' => '2300',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
        Account::create([
            'company_id' => $this->companyId,
            'code' => '1200',
            'name' => 'Bank Account',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank_account' => true,
        ]);
    }

    public function test_full_vat_lifecycle(): void
    {
        // ── Stage 1: Create tax configuration ──
        $vat = TaxType::create([
            'company_id' => $this->companyId,
            'code' => 'VAT',
            'name' => 'Value Added Tax',
            'category' => 'VAT',
        ]);

        $jurisdiction = TaxJurisdiction::create([
            'company_id' => $this->companyId,
            'code' => 'MWI',
            'name' => 'Malawi',
            'country' => 'MW',
            'authority' => 'Malawi Revenue Authority',
            'active' => true,
        ]);

        $code = TaxCode::create([
            'company_id' => $this->companyId,
            'code' => 'VAT_STD',
            'name' => 'Standard VAT',
            'tax_type_id' => $vat->id,
            'jurisdiction_id' => $jurisdiction->id,
            'treatment' => 'INCLUSIVE',
            'effective_from' => '2024-01-01',
            'is_active' => true,
        ]);

        TaxCodeRate::create([
            'tax_code_id' => $code->id,
            'rate_pct' => 16.5,
            'effective_from' => '2024-01-01',
            'effective_to' => null,
        ]);

        $period = TaxPeriod::create([
            'company_id' => $this->companyId,
            'tax_type_id' => $vat->id,
            'label' => 'Jul 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'filing_due_date' => '2026-08-25',
            'status' => 'OPEN',
        ]);

        // ── Stage 2: Tax registration ──
        $regService = app(TaxRegistrationService::class);
        $this->assertFalse($regService->checkRegistration($this->companyId, 'company', $this->companyId, $vat->id));
        $regService->register($this->companyId, 'company', $this->companyId, $vat->id, $jurisdiction->id, 'REG-001', '2024-01-01');
        $this->assertTrue($regService->checkRegistration($this->companyId, 'company', $this->companyId, $vat->id));

        // ── Stage 2: Engine calculation ──
        $engine = app(TaxEngine::class);
        $salesAccount = Account::where('company_id', $this->companyId)->where('code', '4000')->first();
        $taxPayable = Account::where('company_id', $this->companyId)->where('code', '2300')->first();

        $result = $engine->calculateAndPostTax([
            'company_id' => $this->companyId,
            'date' => '2026-07-15',
            'source_module' => 'sales',
            'source_id' => 1,
            'reference' => 'INV-001',
            'user_id' => $this->user->id,
            'period_id' => $period->id,
            'account_id' => $salesAccount->id,
            'tax_account_id' => $taxPayable->id,
            'base_amount' => 1000.00,
            'tax_code_id' => $code->id,
            'side' => 'OUTPUT',
            'memo' => 'Sale to customer',
        ]);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(1000.00, $result->base_amount, 0.01);
        $this->assertEqualsWithDelta(165.00, $result->tax_amount, 0.01);

        // Second transaction (input tax)
        $result2 = $engine->calculateAndPostTax([
            'company_id' => $this->companyId,
            'date' => '2026-07-20',
            'source_module' => 'purchases',
            'source_id' => 1,
            'reference' => 'BILL-001',
            'user_id' => $this->user->id,
            'period_id' => $period->id,
            'account_id' => $salesAccount->id,
            'tax_account_id' => $taxPayable->id,
            'base_amount' => 500.00,
            'tax_code_id' => $code->id,
            'side' => 'INPUT',
            'memo' => 'Purchase from supplier',
        ]);

        $this->assertNotNull($result2);
        $this->assertEqualsWithDelta(500.00, $result2->base_amount, 0.01);
        $this->assertEqualsWithDelta(82.50, $result2->tax_amount, 0.01);

        // ── Stage 3: Generate return ──
        $returnService = app(TaxReturnService::class);
        $return = $returnService->generateReturn($this->companyId, $period->id, $this->user->id);

        $this->assertNotNull($return);
        $this->assertEquals('draft', $return->status);
        $this->assertEqualsWithDelta(165.00, $return->output_tax, 0.01);
        $this->assertEqualsWithDelta(82.50, $return->input_tax, 0.01);
        $this->assertEqualsWithDelta(82.50, $return->net_payable, 0.01);
        $this->assertCount(5, $return->lines); // A, B, C, D + breakdown

        // ── Stage 3: Approve return ──
        // First need status = 'submitted' for approve to work
        $return->update(['status' => 'submitted']);
        $approved = $returnService->approve($this->companyId, $return->id, $this->user->id);
        $this->assertEquals('approved', $approved->status);

        // Period should be locked
        $period->refresh();
        $this->assertEquals('CLOSED', $period->status);

        // ── Stage 3: File return ──
        $filed = $returnService->file($this->companyId, $return->id, 'MRA-2026-001');
        $this->assertEquals('filed', $filed->status);
        $this->assertEquals('MRA-2026-001', $filed->reference);

        // ── Stage 3: Record payment ──
        $bankAccount = Account::where('company_id', $this->companyId)->where('code', '1200')->first();
        $paymentService = app(TaxPaymentService::class);
        $payment = $paymentService->recordPayment($this->companyId, [
            'tax_type_id' => $vat->id,
            'period_id' => $period->id,
            'amount' => 82.50,
            'payment_date' => '2026-08-25',
            'bank_account_id' => $bankAccount->id,
            'payment_ref' => 'TXP-001',
        ], $this->user->id);

        $this->assertNotNull($payment);
        $this->assertEquals('confirmed', $payment->status);

        // ── Stage 3: Void payment ──
        $voided = $paymentService->voidPayment($this->companyId, $payment->id);
        $this->assertEquals('voided', $voided->status);

        // ── Stage 3: WHT certificate ──
        $whtType = TaxType::create([
            'company_id' => $this->companyId,
            'code' => 'WHT',
            'name' => 'Withholding Tax',
            'category' => 'WHT',
        ]);

        $whtCode = TaxCode::create([
            'company_id' => $this->companyId,
            'code' => 'WHT_STD',
            'name' => 'Standard WHT',
            'tax_type_id' => $whtType->id,
            'jurisdiction_id' => $jurisdiction->id,
            'treatment' => 'EXCLUSIVE',
            'effective_from' => '2024-01-01',
            'is_active' => true,
        ]);

        $whtCertService = app(WhtCertificateService::class);
        $cert = $whtCertService->createFromForm($this->companyId, [
            'supplier_id' => $bankAccount->id,
            'tax_code_id' => $whtCode->id,
            'period_id' => $period->id,
            'gross_amount' => 500.00,
            'tax_amount' => 75.00,
        ], $this->user->id);

        $this->assertNotNull($cert);
        $this->assertEquals('issued', $cert->status);

        // Revoke it
        $revoked = $whtCertService->revoke($this->companyId, $cert->id, $this->user->id);
        $this->assertEquals('revoked', $revoked->status);

        // ── Stage 4: Route rendering ──
        $routes = [
            'accounting.taxation.dashboard',
            'accounting.taxation.codes',
            'accounting.taxation.types',
            'accounting.taxation.rates',
            'accounting.taxation.exemptions',
            'accounting.taxation.jurisdictions',
            'accounting.taxation.accounts',
            'accounting.taxation.periods',
            'accounting.taxation.reconciliation',
            'accounting.taxation.reports',
            'accounting.taxation.audit-trail',
            'accounting.taxation.position',
            'accounting.taxation.control-account',
            'accounting.taxation.payments',
            'accounting.taxation.recognition-rules',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)->get(route($route));
            $response->assertStatus(200);
        }
    }

    public function test_adjustment_lifecycle(): void
    {
        $vat = TaxType::create([
            'company_id' => $this->companyId,
            'code' => 'VAT',
            'name' => 'VAT',
            'category' => 'VAT',
        ]);

        $jurisdiction = TaxJurisdiction::create([
            'company_id' => $this->companyId,
            'code' => 'MWI',
            'name' => 'Malawi',
            'country' => 'MW',
            'authority' => 'MRA',
            'active' => true,
        ]);

        $period = TaxPeriod::create([
            'company_id' => $this->companyId,
            'tax_type_id' => $vat->id,
            'label' => 'Jul 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'filing_due_date' => '2026-08-25',
            'status' => 'OPEN',
        ]);

        // Create via controller route
        $this->actingAs($this->user)->post(route('accounting.taxation.adjustments.store'), [
            'period_id' => $period->id,
            'tax_type_id' => $vat->id,
            'amount' => 100,
            'direction' => 'ADD',
            'reason' => 'Late filing fee',
        ])->assertRedirect();

        $adj = \App\Models\TaxAdjustment::where('company_id', $this->companyId)->first();
        $this->assertNotNull($adj);
        $this->assertEquals('PENDING', $adj->status);

        // Approve
        $this->actingAs($this->user)
            ->post(route('accounting.taxation.adjustments.approve', $adj->id))
            ->assertRedirect();
        $adj->refresh();
        $this->assertEquals('APPROVED', $adj->status);

        // Audit trail should have both entries
        $logs = \App\Models\TaxAuditTrail::where('company_id', $this->companyId)
            ->where('entity_kind', 'TAX_ADJUSTMENT')
            ->get();
        $this->assertCount(2, $logs);
    }
}
