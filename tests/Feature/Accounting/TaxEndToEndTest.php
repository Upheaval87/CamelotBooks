<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxObligation;
use App\Models\TaxPeriod;
use App\Models\TaxType;
use App\Models\User;
use App\Services\Tax\TaxEngine;
use App\Services\Tax\TaxObligationService;
use App\Services\Tax\TaxPaymentService;
use App\Services\Tax\TaxRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    }

    public function test_full_vat_obligation_lifecycle_without_manual_status_forcing(): void
    {
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
            'active' => true,
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

        $regService = app(TaxRegistrationService::class);
        $regService->register($this->companyId, 'company', $this->companyId, $vat->id, $jurisdiction->id, 'REG-001', '2024-01-01');

        $engine = app(TaxEngine::class);
        $salesAccount = Account::where('company_id', $this->companyId)->where('code', '4000')->first();
        $taxPayable = Account::where('company_id', $this->companyId)->where('code', '2300')->first();

        // ── No obligation exists yet ──
        $this->assertDatabaseMissing('tax_obligations', [
            'company_id' => $this->companyId,
            'period_id' => $period->id,
        ]);

        // ── First posted transaction auto-creates the obligation (OPEN → CALCULATING) ──
        $r1 = $engine->calculateAndPostTax([
            'company_id' => $this->companyId,
            'date' => '2026-07-15',
            'source_module' => 'sales',
            'source_kind' => 'sales',
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
        $this->assertEqualsWithDelta(1000.00, $r1->base_amount, 0.01);
        $this->assertEqualsWithDelta(165.00, $r1->tax_amount, 0.01);

        $obligation = TaxObligation::where('company_id', $this->companyId)
            ->where('period_id', $period->id)->first();
        $this->assertNotNull($obligation);
        $this->assertNotEquals(TaxObligation::STATUS_OPEN, $obligation->status);

        // ── Second (completing) posted transaction advances to READY_TO_RECONCILE ──
        $r2 = $engine->calculateAndPostTax([
            'company_id' => $this->companyId,
            'date' => '2026-07-20',
            'source_module' => 'purchases',
            'source_kind' => 'purchases',
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
        $this->assertEqualsWithDelta(500.00, $r2->base_amount, 0.01);
        $this->assertEqualsWithDelta(82.50, $r2->tax_amount, 0.01);

        $obligation->refresh();
        $this->assertEquals(TaxObligation::STATUS_READY_TO_RECONCILE, $obligation->status);

        // ── Reconcile. No GL was posted, so the working-paper variance is the full
        // calculated net (82.50) — the clerk signs it off with a waiver → RECONCILED ──
        $outputCalculated = (float) $r1->tax_amount;
        $inputCalculated = (float) $r2->tax_amount;
        $netCalculated = round($outputCalculated - $inputCalculated, 2); // 82.50
        $variance = $netCalculated; // no GL was posted, so GL movement is 0

        $service = app(TaxObligationService::class);
        $reconciled = $service->reconcile(
            $this->companyId,
            $period->id,
            $variance,
            $this->user->id,
            true,
            'GL lines are booked by the referencing document post; working paper variance accepted.'
        );
        $this->assertEquals(TaxObligation::STATUS_RECONCILED, $reconciled->status);
        $this->assertTrue($reconciled->variance_waived);
        $this->assertNotNull($reconciled->variance_waived_reason);

        // ── draft → RETURN_DRAFTED ──
        $return = $service->draftReturn($this->companyId, $period->id, $this->user->id);
        $this->assertNotNull($return);
        $this->assertEquals('draft', $return->status);
        $this->assertEqualsWithDelta(165.00, $return->output_tax, 0.01);
        $this->assertEqualsWithDelta(82.50, $return->input_tax, 0.01);
        $this->assertEqualsWithDelta(82.50, $return->net_payable, 0.01);
        $this->assertCount(5, $return->lines);
        $reconciled->refresh();
        $this->assertEquals(TaxObligation::STATUS_RETURN_DRAFTED, $reconciled->status);

        // ── approve → RETURN_APPROVED ──
        $approvedObligation = $service->approveReturn($this->companyId, $period->id, $this->user->id);
        $this->assertEquals(TaxObligation::STATUS_RETURN_APPROVED, $approvedObligation->status);
        $return->refresh();
        $this->assertEquals('approved', $return->status);

        // ── file → FILED ──
        $filedObligation = $service->fileReturn($this->companyId, $period->id, 'MRA-2026-001');
        $this->assertEquals(TaxObligation::STATUS_FILED, $filedObligation->status);
        $return->refresh();
        $this->assertEquals('filed', $return->status);
        $this->assertEquals('MRA-2026-001', $return->reference);

        // ── pay → PAID (payment covers net payable; advancePaidIfCovered auto-advances) ──
        $bankAccount = Account::where('company_id', $this->companyId)->where('code', '1150')->first();
        app(TaxPaymentService::class)->recordPayment($this->companyId, [
            'tax_type_id' => $vat->id,
            'period_id' => $period->id,
            'amount' => 82.50,
            'payment_date' => '2026-08-25',
            'bank_account_id' => $bankAccount->id,
            'payment_ref' => 'TXP-001',
        ], $this->user->id);

        $this->assertEquals(
            TaxObligation::STATUS_PAID,
            TaxObligation::where('company_id', $this->companyId)
                ->where('period_id', $period->id)->value('status')
        );

        // ── close → CLOSED ──
        $closed = $service->close($this->companyId, $period->id, $this->user->id);
        $this->assertEquals(TaxObligation::STATUS_CLOSED, $closed->status);

        $period->refresh();
        $this->assertEquals('CLOSED', $period->status);
        $this->assertTrue((bool) $period->locked);
    }

    public function test_reconcile_cannot_be_run_twice_once_reconciled(): void
    {
        // Gate-block test: RECONCILED is a hard gate. Once reconciled, a second
        // reconcile call is rejected (the service expects READY_TO_RECONCILE),
        // so a period can never be reconciled twice.
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
            'active' => true,
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
            'label' => 'Aug 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'filing_due_date' => '2026-09-25',
            'status' => 'OPEN',
        ]);

        $salesAccount = Account::where('company_id', $this->companyId)->where('code', '4000')->first();
        $taxPayable = Account::where('company_id', $this->companyId)->where('code', '2300')->first();
        $engine = app(TaxEngine::class);

        // A single posted transaction advances the obligation to READY_TO_RECONCILE.
        $engine->calculateAndPostTax([
            'company_id' => $this->companyId,
            'date' => '2026-08-10',
            'source_module' => 'sales',
            'source_kind' => 'sales',
            'source_id' => 1,
            'reference' => 'INV-002',
            'user_id' => $this->user->id,
            'period_id' => $period->id,
            'account_id' => $salesAccount->id,
            'tax_account_id' => $taxPayable->id,
            'base_amount' => 1000.00,
            'tax_code_id' => $code->id,
            'side' => 'OUTPUT',
            'memo' => 'Single sale',
        ]);

        $obligation = TaxObligation::where('company_id', $this->companyId)
            ->where('period_id', $period->id)->first();
        $this->assertEquals(TaxObligation::STATUS_READY_TO_RECONCILE, $obligation->status);

        // First reconcile succeeds → RECONCILED (zero variance, no waiver needed).
        $service = app(TaxObligationService::class);
        $reconciled = $service->reconcile($this->companyId, $period->id, 0.0, $this->user->id);
        $this->assertEquals(TaxObligation::STATUS_RECONCILED, $reconciled->status);

        // Second reconcile is blocked (422) — the RECONCILED gate cannot be re-entered.
        try {
            $service->reconcile($this->companyId, $period->id, 0.0, $this->user->id);
            $this->fail('A second reconcile after RECONCILED should be rejected.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
        }
    }

    public function test_reconcile_blocks_nonzero_variance_without_waiver(): void
    {
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
            'active' => true,
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
            'label' => 'Sep 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'filing_due_date' => '2026-10-25',
            'status' => 'OPEN',
        ]);

        $salesAccount = Account::where('company_id', $this->companyId)->where('code', '4000')->first();
        $taxPayable = Account::where('company_id', $this->companyId)->where('code', '2300')->first();
        $engine = app(TaxEngine::class);

        // Post two transactions so the obligation reaches READY_TO_RECONCILE.
        foreach (['sales', 'purchases'] as $kind) {
            $engine->calculateAndPostTax([
                'company_id' => $this->companyId,
                'date' => '2026-09-10',
                'source_module' => $kind,
                'source_kind' => $kind,
                'source_id' => 1,
                'reference' => 'REF-' . $kind,
                'user_id' => $this->user->id,
                'period_id' => $period->id,
                'account_id' => $salesAccount->id,
                'tax_account_id' => $taxPayable->id,
                'base_amount' => 1000.00,
                'tax_code_id' => $code->id,
                'side' => $kind === 'sales' ? 'OUTPUT' : 'INPUT',
                'memo' => 'Transaction',
            ]);
        }

        $obligation = TaxObligation::where('company_id', $this->companyId)
            ->where('period_id', $period->id)->first();
        $this->assertEquals(TaxObligation::STATUS_READY_TO_RECONCILE, $obligation->status);

        // Non-zero variance and no waiver → reconcile is blocked (422).
        // Reconcile with a non-zero variance and no waiver is rejected (422).
        try {
            app(TaxObligationService::class)->reconcile($this->companyId, $period->id, 5.00, $this->user->id);
            $this->fail('A non-zero variance without a waiver should be rejected.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
        }
    }
}