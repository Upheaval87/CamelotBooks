<?php

namespace Tests\Unit\Services\Tax;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxExemption;
use App\Models\TaxJurisdiction;
use App\Models\TaxPeriod;
use App\Models\TaxRecognitionRule;
use App\Models\TaxTransaction;
use App\Models\TaxType;
use App\Models\User;
use App\Services\Tax\TaxEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TaxEngineTest extends TestCase
{
    use RefreshDatabase;

    protected TaxEngine $engine;
    protected int $companyId;
    protected int $userId;
    protected TaxType $vat;
    protected TaxJurisdiction $jurisdiction;
    protected TaxCode $standardCode;
    protected TaxCode $inclusiveCode;
    protected TaxCode $zeroRatedCode;
    protected TaxCode $exemptCode;
    protected Account $taxReceivable;
    protected Account $taxPayable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name'       => 'Tax Test Co',
            'company_code' => 'TAXTEST',
            'is_active'  => true,
        ]);
        $this->companyId = $this->company->id;

        $user = User::factory()->create();
        $this->userId = $user->id;

        AccountingPeriod::create([
            'company_id' => $this->companyId,
            'label'      => '2026',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'status'     => 'open',
        ]);

        $this->vat = TaxType::create([
            'company_id' => $this->companyId,
            'code'       => 'VAT',
            'name'       => 'Value Added Tax',
            'category'   => 'VAT',
            'active'     => true,
        ]);

        $this->jurisdiction = TaxJurisdiction::create([
            'company_id' => $this->companyId,
            'code'       => 'MWI',
            'name'       => 'Malawi',
            'country'    => 'Malawi',
            'authority'  => 'MRA',
            'active'     => true,
        ]);

        // Standard tax code with 16.5% rate
        $this->standardCode = TaxCode::create([
            'company_id'     => $this->companyId,
            'code'           => 'VAT_STD',
            'name'           => 'Standard VAT',
            'tax_type_id'    => $this->vat->id,
            'jurisdiction_id' => $this->jurisdiction->id,
            'treatment'      => 'STANDARD',
            'price_basis'    => 'EXCLUSIVE',
            'rounding_mode'  => 'HALF_UP',
            'rounding_level' => 'LINE',
            'effective_from' => '2024-01-01',
            'active'         => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id'   => $this->standardCode->id,
            'rate_pct'      => 16.5,
            'effective_from' => '2024-01-01',
        ]);

        // Inclusive tax code
        $this->inclusiveCode = TaxCode::create([
            'company_id'     => $this->companyId,
            'code'           => 'VAT_INC',
            'name'           => 'Standard VAT (Inclusive)',
            'tax_type_id'    => $this->vat->id,
            'jurisdiction_id' => $this->jurisdiction->id,
            'treatment'      => 'STANDARD',
            'price_basis'    => 'INCLUSIVE',
            'rounding_mode'  => 'HALF_UP',
            'rounding_level' => 'LINE',
            'effective_from' => '2024-01-01',
            'active'         => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id'   => $this->inclusiveCode->id,
            'rate_pct'      => 16.5,
            'effective_from' => '2024-01-01',
        ]);

        // Zero rated
        $this->zeroRatedCode = TaxCode::create([
            'company_id'     => $this->companyId,
            'code'           => 'VAT_ZERO',
            'name'           => 'Zero Rated',
            'tax_type_id'    => $this->vat->id,
            'jurisdiction_id' => $this->jurisdiction->id,
            'treatment'      => 'ZERO_RATED',
            'price_basis'    => 'EXCLUSIVE',
            'rounding_mode'  => 'HALF_UP',
            'rounding_level' => 'LINE',
            'effective_from' => '2024-01-01',
            'active'         => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id'   => $this->zeroRatedCode->id,
            'rate_pct'      => 0,
            'effective_from' => '2024-01-01',
        ]);

        // Exempt
        $this->exemptCode = TaxCode::create([
            'company_id'     => $this->companyId,
            'code'           => 'VAT_EXM',
            'name'           => 'Exempt',
            'tax_type_id'    => $this->vat->id,
            'jurisdiction_id' => $this->jurisdiction->id,
            'treatment'      => 'EXEMPT',
            'price_basis'    => 'EXCLUSIVE',
            'rounding_mode'  => 'HALF_UP',
            'rounding_level' => 'LINE',
            'effective_from' => '2024-01-01',
            'active'         => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id'   => $this->exemptCode->id,
            'rate_pct'      => 0,
            'effective_from' => '2024-01-01',
        ]);

        // Accounts
        $this->taxReceivable = Account::create([
            'company_id' => $this->companyId,
            'code'       => '1150',
            'name'       => 'Tax Receivable',
            'type'       => 'asset',
            'sub_type'   => 'current_asset',
        ]);
        $this->taxPayable = Account::create([
            'company_id' => $this->companyId,
            'code'       => '2100',
            'name'       => 'Tax Payable',
            'type'       => 'liability',
            'sub_type'   => 'current_liability',
        ]);

        // Recognition rule
        TaxRecognitionRule::create([
            'company_id'  => $this->companyId,
            'tax_type_id' => $this->vat->id,
            'basis'       => 'INVOICE',
        ]);

        // Wire the engine via the container so JournalPostingEngine gets NumberingSequenceService
        $this->engine = app(TaxEngine::class);
    }

    // ── §0.1 — Effective-date overlap ──────────────────────────────

    public function test_overlapping_rate_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->validateNoOverlappingRates(
            $this->standardCode->id,
            '2024-06-01',
            null
        );
    }

    public function test_non_overlapping_rate_passes(): void
    {
        // This should not throw — the new rate starts after the existing one ends
        $this->standardCode->rates()->update(['effective_to' => '2024-12-31']);

        $this->engine->validateNoOverlappingRates(
            $this->standardCode->id,
            '2025-01-01',
            null
        );

        $this->assertTrue(true); // No exception = pass
    }

    public function test_overlapping_registration_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Create a registration first
        \App\Models\TaxRegistration::create([
            'company_id'     => $this->companyId,
            'entity_kind'    => 'COMPANY',
            'entity_id'      => 1,
            'jurisdiction_id' => $this->jurisdiction->id,
            'tax_type_id'    => $this->vat->id,
            'reg_number'     => 'VAT123',
            'effective_from' => '2024-01-01',
            'status'         => 'active',
        ]);

        // Try to create an overlapping one
        $this->engine->validateNoOverlappingRegistrations(
            $this->companyId,
            'COMPANY',
            1,
            $this->vat->id,
            $this->jurisdiction->id,
            '2024-06-01',
            null
        );
    }

    // ── §1.9 — Round-trip tax calculation ──────────────────────────

    public function test_compute_tax_exclusive(): void
    {
        $result = $this->engine->computeTax(1000, 16.5, 'STANDARD', 'EXCLUSIVE');

        $this->assertEquals(1000, $result['base_amount']);
        $this->assertEquals(165.00, $result['tax_amount']);
        $this->assertEquals(1165.00, $result['gross_amount']);
        $this->assertEquals(1000, $result['net_amount']);
    }

    public function test_compute_tax_inclusive(): void
    {
        $result = $this->engine->computeTax(1165, 16.5, 'STANDARD', 'INCLUSIVE');

        $this->assertEquals(1165, $result['base_amount']);
        $this->assertEquals(165.00, $result['tax_amount'], '', 0.02);
        $this->assertEquals(1165, $result['gross_amount']);
        $this->assertEquals(1000.00, $result['net_amount'], '', 0.02);
    }

    public function test_compute_tax_zero_rated(): void
    {
        $result = $this->engine->computeTax(1000, 0, 'ZERO_RATED', 'EXCLUSIVE');

        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(1000, $result['gross_amount']);
        $this->assertEquals(1000, $result['net_amount']);
    }

    public function test_compute_tax_exempt(): void
    {
        $result = $this->engine->computeTax(1000, 0, 'EXEMPT', 'EXCLUSIVE');

        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(1000, $result['gross_amount']);
    }

    public function test_round_tax_half_up(): void
    {
        $this->assertEquals(1.23, $this->engine->roundTax(1.225, 'HALF_UP'));
        $this->assertEquals(1.22, $this->engine->roundTax(1.225, 'HALF_DOWN'));
        $this->assertEquals(1.22, $this->engine->roundTax(1.225, 'HALF_EVEN'));
    }

    public function test_round_trip_validation_exclusive(): void
    {
        $this->assertTrue($this->engine->validateRoundTrip(1000, 16.5, 'EXCLUSIVE'));
    }

    public function test_round_trip_validation_inclusive(): void
    {
        $this->assertTrue($this->engine->validateRoundTrip(1165, 16.5, 'INCLUSIVE'));
    }

    // ── Context validation ─────────────────────────────────────────

    public function test_missing_required_key_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->calculateAndPostTax([
            'company_id' => $this->companyId,
            // Missing tax_code_id
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
        ]);
    }

    public function test_invalid_side_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->calculateAndPostTax([
            'company_id'   => $this->companyId,
            'tax_code_id'  => $this->standardCode->id,
            'base_amount'  => 1000,
            'side'         => 'INVALID',
        ]);
    }

    public function test_negative_amount_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->calculateAndPostTax([
            'company_id'   => $this->companyId,
            'tax_code_id'  => $this->standardCode->id,
            'base_amount'  => -100,
            'side'         => 'OUTPUT',
        ]);
    }

    // ── §1.13 — calculateAndPostTax ───────────────────────────────

    public function test_calculate_and_post_output_standard(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 5000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-01-15',
        ]);

        $this->assertNotNull($txn->id);
        $this->assertEquals(5000, $txn->base_amount);
        $this->assertEquals(825.00, $txn->tax_amount); // 5000 * 16.5%
        $this->assertEquals(5825.00, $txn->gross_amount);
        $this->assertEquals('OUTPUT', $txn->side);
        $this->assertEquals('POSTED', $txn->status);
        $this->assertEquals('INVOICE', $txn->recognition_basis);
        $this->assertNotNull($txn->recognized_at);
    }

    public function test_calculate_and_post_input_standard(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 2000,
            'side'        => 'INPUT',
            'source_kind' => 'PURCHASE_BILL',
            'date'        => '2026-01-15',
        ]);

        $this->assertEquals('INPUT', $txn->side);
        $this->assertEquals(330.00, $txn->tax_amount);
    }

    public function test_calculate_and_post_with_exemption(): void
    {
        $exemption = TaxExemption::create([
            'company_id'     => $this->companyId,
            'code'           => 'EXM-FOOD',
            'name'           => 'Food Exemption',
            'reason'         => 'Basic food items exempt from VAT',
            'scope'          => 'BOTH',
            'tax_type_id'    => $this->vat->id,
            'effective_from' => '2024-01-01',
            'active'         => true,
        ]);

        $txn = $this->engine->calculateAndPostTax([
            'company_id'    => $this->companyId,
            'user_id'       => 1,
            'tax_code_id'   => $this->standardCode->id,
            'base_amount'   => 1000,
            'side'          => 'OUTPUT',
            'exemption_id'  => $exemption->id,
            'source_kind'   => 'SALES_INVOICE',
            'date'          => '2026-01-15',
        ]);

        $this->assertEquals(0, $txn->tax_amount);
        $this->assertEquals('Basic food items exempt from VAT', $txn->exemption_reason);
    }

    public function test_calculate_and_post_with_apportionment(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'          => $this->companyId,
            'user_id'             => 1,
            'tax_code_id'         => $this->standardCode->id,
            'base_amount'         => 1000,
            'side'                => 'INPUT',
            'apportionment_pct'   => 75.0,
            'source_kind'         => 'PURCHASE_BILL',
            'date'                => '2026-01-15',
        ]);

        $this->assertEquals(165.00, $txn->tax_amount);
        $this->assertEquals(75.0, $txn->apportionment_pct);
        $this->assertEquals(123.75, $txn->recoverable_tax_amount); // 165 * 75%
    }

    public function test_calculate_and_post_zero_rated(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->zeroRatedCode->id,
            'base_amount' => 5000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-01-15',
        ]);

        $this->assertEquals(0, $txn->tax_amount);
        $this->assertEquals(5000, $txn->gross_amount);
    }

    public function test_no_active_rate_throws(): void
    {
        // Delete all rates for the code
        $this->standardCode->rates()->delete();

        $this->expectException(InvalidArgumentException::class);
        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-01-15',
        ]);
    }

    public function test_period_is_auto_created_when_missing(): void
    {
        $this->assertEquals(0, TaxPeriod::count());

        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-03-15',
        ]);

        $this->assertEquals(1, TaxPeriod::count());
        $period = TaxPeriod::first();
        $this->assertEquals('Mar 2026', $period->label);
        $this->assertEquals('OPEN', $period->status);
    }

    public function test_existing_period_is_reused(): void
    {
        TaxPeriod::create([
            'company_id'      => $this->companyId,
            'tax_type_id'     => $this->vat->id,
            'label'           => 'Jan 2026',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-01-31',
            'status'          => 'OPEN',
            'filing_due_date' => '2026-02-25',
        ]);

        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-01-15',
        ]);

        $this->assertEquals(1, TaxPeriod::count()); // No new period created
    }

    // ── Audit trail ────────────────────────────────────────────────

    public function test_audit_trail_is_written(): void
    {
        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-01-15',
        ]);

        $this->assertDatabaseHas('tax_audit_trail', [
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'entity_kind' => 'tax_transaction',
        ]);
    }

    // ── §1.8 — Full calculateAndPostTax round-trip ─────────────────

    public function test_full_round_trip_with_journal_posting(): void
    {
        // Calculate tax
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 10000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-06-15',
        ]);

        // Post to GL
        $je = $this->engine->postTaxJournal([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'date'        => '2026-06-15',
            'source_module' => 'invoice',
            'reference'   => 'INV-0001',
            'memo'        => 'Tax for INV-0001',
            'branch_id'   => null,
            'lines'       => [$txn],
        ]);

        $this->assertNotNull($je);
        $this->assertDatabaseHas('journal_entries', [
            'company_id'    => $this->companyId,
            'source_module' => 'tax',
            'reference'     => 'INV-0001',
        ]);

        // Verify balanced JE
        $totalDebit  = $je->lines()->sum('debit');
        $totalCredit = $je->lines()->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
    }
}
