<?php

namespace Tests\Unit\Services\Tax;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\TaxAuditTrail;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxPayment;
use App\Models\TaxPeriod;
use App\Models\TaxRecognitionRule;
use App\Models\TaxRegistration;
use App\Models\TaxReturn;
use App\Models\TaxTransaction;
use App\Models\TaxType;
use App\Models\User;
use App\Services\Tax\TaxEngine;
use App\Services\Tax\TaxRegistrationService;
use App\Services\Tax\TaxReturnService;
use App\Services\Tax\TaxPaymentService;
use App\Services\Tax\WhtCertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxGLIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TaxEngine $engine;
    protected TaxRegistrationService $regService;
    protected TaxReturnService $returnService;
    protected TaxPaymentService $paymentService;
    protected WhtCertificateService $whtService;
    protected Company $company;
    protected int $companyId;
    protected int $userId;
    protected TaxType $vat;
    protected TaxType $wht;
    protected TaxJurisdiction $jurisdiction;
    protected TaxCode $standardCode;
    protected TaxCode $whtCode;
    protected Account $taxReceivable;
    protected Account $taxPayable;
    protected Account $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name'         => 'Tax Integration Co',
            'company_code' => 'TAXINT',
            'is_active'    => true,
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

        $this->wht = TaxType::create([
            'company_id' => $this->companyId,
            'code'       => 'WHT',
            'name'       => 'Withholding Tax',
            'category'   => 'WHT',
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

        $this->whtCode = TaxCode::create([
            'company_id'     => $this->companyId,
            'code'           => 'WHT_SUP',
            'name'           => 'WHT on Supplies',
            'tax_type_id'    => $this->wht->id,
            'jurisdiction_id' => $this->jurisdiction->id,
            'treatment'      => 'STANDARD',
            'price_basis'    => 'EXCLUSIVE',
            'rounding_mode'  => 'HALF_UP',
            'rounding_level' => 'LINE',
            'effective_from' => '2024-01-01',
            'active'         => true,
        ]);
        TaxCodeRate::create([
            'tax_code_id'   => $this->whtCode->id,
            'rate_pct'      => 10,
            'effective_from' => '2024-01-01',
        ]);

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
        $this->bankAccount = Account::create([
            'company_id' => $this->companyId,
            'code'       => '1000',
            'name'       => 'Main Bank',
            'type'       => 'asset',
            'sub_type'   => 'current_asset',
        ]);

        TaxRecognitionRule::create([
            'company_id'  => $this->companyId,
            'tax_type_id' => $this->vat->id,
            'basis'       => 'INVOICE',
        ]);
        TaxRecognitionRule::create([
            'company_id'  => $this->companyId,
            'tax_type_id' => $this->wht->id,
            'basis'       => 'INVOICE',
        ]);

        $this->engine          = app(TaxEngine::class);
        $this->regService      = app(TaxRegistrationService::class);
        $this->returnService   = app(TaxReturnService::class);
        $this->paymentService  = app(TaxPaymentService::class);
        $this->whtService      = app(WhtCertificateService::class);
    }

    // ── Full GL round-trip: calculate → post → verify balanced ──

    public function test_output_vat_gl_posting_is_balanced(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 10000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-03-15',
        ]);

        $je = $this->engine->postTaxJournal([
            'company_id'   => $this->companyId,
            'user_id'      => $this->userId,
            'date'         => '2026-03-15',
            'source_module' => 'invoice',
            'reference'    => 'INV-1001',
            'memo'         => 'Tax for INV-1001',
            'lines'        => [$txn],
        ]);

        $this->assertNotNull($je);
        $this->assertDatabaseHas('journal_entries', [
            'company_id'    => $this->companyId,
            'source_module' => 'tax',
            'reference'     => 'INV-1001',
        ]);

        $totalDebit  = $je->lines()->sum('debit');
        $totalCredit = $je->lines()->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(1650.00, $totalDebit);
    }

    public function test_input_vat_gl_posting_is_balanced(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 5000,
            'side'        => 'INPUT',
            'source_kind' => 'PURCHASE_BILL',
            'date'        => '2026-03-15',
        ]);

        $je = $this->engine->postTaxJournal([
            'company_id'   => $this->companyId,
            'user_id'      => $this->userId,
            'date'         => '2026-03-15',
            'source_module' => 'bill',
            'reference'    => 'BILL-5001',
            'memo'         => 'Tax for BILL-5001',
            'lines'        => [$txn],
        ]);

        $this->assertNotNull($je);
        $totalDebit  = $je->lines()->sum('debit');
        $totalCredit = $je->lines()->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(825.00, $totalDebit);
    }

    public function test_mixed_output_and_input_gl_posting(): void
    {
        $outputTxn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 20000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-04-10',
        ]);

        $inputTxn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 8000,
            'side'        => 'INPUT',
            'source_kind' => 'PURCHASE_BILL',
            'date'        => '2026-04-10',
        ]);

        $je = $this->engine->postTaxJournal([
            'company_id'   => $this->companyId,
            'user_id'      => $this->userId,
            'date'         => '2026-04-10',
            'source_module' => 'tax',
            'reference'    => 'TAX-MIXED-001',
            'memo'         => 'Mixed output/input tax',
            'lines'        => [$outputTxn, $inputTxn],
        ]);

        $this->assertNotNull($je);
        $totalDebit  = $je->lines()->sum('debit');
        $totalCredit = $je->lines()->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        // output tax = 3300, input tax = 1320
        $this->assertEquals(3300 + 1320, $totalDebit);
    }

    public function test_zero_tax_returns_null_journal(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 0,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-05-01',
        ]);

        $je = $this->engine->postTaxJournal([
            'company_id'   => $this->companyId,
            'user_id'      => $this->userId,
            'date'         => '2026-05-01',
            'source_module' => 'tax',
            'reference'    => 'TAX-ZERO-001',
            'memo'         => 'Zero tax',
            'lines'        => [$txn],
        ]);

        $this->assertNull($je);
    }

    // ── Registration service ──

    public function test_registration_check_and_register(): void
    {
        $this->assertFalse($this->regService->checkRegistration(
            $this->companyId, 'vendor', 1, $this->vat->id
        ));

        $reg = $this->regService->register(
            $this->companyId,
            'vendor',
            1,
            $this->vat->id,
            $this->jurisdiction->id,
            'VAT-REG-123',
            '2025-01-01',
        );

        $this->assertTrue($this->regService->checkRegistration(
            $this->companyId, 'vendor', 1, $this->vat->id
        ));
        $this->assertEquals('active', $reg->status);
        $this->assertEquals('VAT-REG-123', $reg->reg_number);
    }

    public function test_deregistration_stops_check(): void
    {
        $reg = $this->regService->register(
            $this->companyId, 'vendor', 1, $this->vat->id,
            $this->jurisdiction->id, 'VAT-REG-456', '2025-01-01',
        );

        $this->assertTrue($this->regService->checkRegistration(
            $this->companyId, 'vendor', 1, $this->vat->id
        ));

        $this->regService->deregister($reg->id);

        $this->assertFalse($this->regService->checkRegistration(
            $this->companyId, 'vendor', 1, $this->vat->id
        ));
    }

    // ── Tax return service ──

    public function test_generate_return_aggregates_transactions(): void
    {
        $period = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 10000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-06-15',
        ])->period;

        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 5000,
            'side'        => 'INPUT',
            'source_kind' => 'PURCHASE_BILL',
            'date'        => '2026-06-20',
        ]);

        $return = $this->returnService->generateReturn(
            $this->companyId,
            $period->id,
            $this->userId,
        );

        $this->assertNotNull($return);
        $this->assertEquals('draft', $return->status);
        $this->assertEquals(1650.00, $return->output_tax);
        $this->assertEquals(825.00, $return->input_tax);
        $this->assertEquals(825.00, $return->net_payable);
        $this->assertEquals(1, $return->version);

        $lines = $return->lines;
        $this->assertGreaterThanOrEqual(4, $lines->count());
    }

    public function test_return_approval_locks_period(): void
    {
        $period = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-07-15',
        ])->period;

        $return = $this->returnService->generateReturn(
            $this->companyId, $period->id, $this->userId,
        );

        $return->update(['status' => 'submitted']);
        $approved = $this->returnService->approve(
            $this->companyId, $return->id, $this->userId,
        );

        $this->assertEquals('approved', $approved->status);
        $this->assertDatabaseHas('tax_periods', [
            'id'     => $period->id,
            'locked' => true,
        ]);
    }

    // ── Tax payment service ──

    public function test_record_and_void_payment(): void
    {
        $period = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 1000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-08-15',
        ])->period;

        $payment = $this->paymentService->recordPayment(
            $this->companyId,
            [
                'tax_type_id'     => $this->vat->id,
                'period_id'       => $period->id,
                'amount'          => 500.00,
                'payment_date'    => '2026-08-20',
                'bank_account_id' => $this->bankAccount->id,
                'payment_ref'     => 'PAY-VOID-001',
            ],
            $this->userId,
        );

        $this->assertEquals('confirmed', $payment->status);
        $this->assertEquals(500.00, $payment->amount);

        $voided = $this->paymentService->voidPayment($this->companyId, $payment->id);
        $this->assertEquals('voided', $voided->status);
    }

    public function test_get_total_paid_for_return(): void
    {
        $period = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 10000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-09-10',
        ])->period;

        $return = $this->returnService->generateReturn(
            $this->companyId, $period->id, $this->userId,
        );

        $this->paymentService->recordPayment(
            $this->companyId,
            [
                'tax_type_id'     => $this->vat->id,
                'period_id'       => $period->id,
                'amount'          => 800.00,
                'payment_date'    => '2026-09-20',
                'bank_account_id' => $this->bankAccount->id,
                'payment_ref'     => 'PAY-002',
            ],
            $this->userId,
        );

        $totalPaid = $this->paymentService->getTotalPaidForReturn(
            $this->companyId, $return->id,
        );
        $this->assertEquals(800.00, $totalPaid);
    }

    // ── WHT certificate service ──

    public function test_generate_wht_certificate(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->whtCode->id,
            'base_amount' => 5000,
            'side'        => 'INPUT',
            'source_kind' => 'PURCHASE_BILL',
            'date'        => '2026-10-15',
        ]);

        $cert = $this->whtService->generate(
            $this->companyId, $txn->id, $this->userId,
            null, $this->bankAccount->id,
        );

        $this->assertEquals('WHT-000001', $cert->cert_number);
        $this->assertEquals('issued', $cert->status);
        $this->assertEquals(500.00, $cert->wht_amount);
        $this->assertEquals(5000.00, $cert->gross);
    }

    public function test_revoke_wht_certificate(): void
    {
        $txn = $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->whtCode->id,
            'base_amount' => 3000,
            'side'        => 'INPUT',
            'source_kind' => 'PURCHASE_BILL',
            'date'        => '2026-11-01',
        ]);

        $cert = $this->whtService->generate(
            $this->companyId, $txn->id, $this->userId,
            null, $this->bankAccount->id,
        );

        $revoked = $this->whtService->revoke($this->companyId, $cert->id, $this->userId);
        $this->assertEquals('revoked', $revoked->status);
    }

    // ── Audit trail integrity ──

    public function test_audit_trail_logged_per_calculation(): void
    {
        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 7500,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-12-01',
        ]);

        $auditCount = TaxAuditTrail::where('company_id', $this->companyId)
            ->where('entity_kind', 'tax_transaction')
            ->count();

        $this->assertEquals(1, $auditCount);

        $audit = TaxAuditTrail::where('company_id', $this->companyId)->first();
        $this->assertEquals('SYSTEM', $audit->approval);
        $this->assertNotNull($audit->new_value);
    }

    // ── Multiple-period scenario ──

    public function test_transactions_create_separate_periods(): void
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

        $this->engine->calculateAndPostTax([
            'company_id'  => $this->companyId,
            'user_id'     => $this->userId,
            'tax_code_id' => $this->standardCode->id,
            'base_amount' => 2000,
            'side'        => 'OUTPUT',
            'source_kind' => 'SALES_INVOICE',
            'date'        => '2026-03-15',
        ]);

        $this->assertEquals(2, TaxPeriod::where('company_id', $this->companyId)->count());
    }
}
