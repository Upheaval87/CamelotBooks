<?php

namespace Tests\Feature\EIS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EisSubmission;
use App\Models\EisTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\EIS\EisSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EisSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected EisSubmissionService $service;
    protected Company $company;
    protected User $user;
    protected EisTerminal $terminal;
    protected \App\Models\PosTerminal $posTerminal;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EisSubmissionService::class);

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'EIS Test Co',
            'company_code' => 'EIS',
            'is_active' => true,
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 1,
            'tax_id' => '12345678',
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '1060',
            'name' => 'Cash in Drawer',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'COGS',
            'type' => 'expense',
            'sub_type' => 'cost_of_goods_sold',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda',
            'sku' => 'SODA-001',
            'type' => 'goods',
            'tracked_as_inventory' => false,
            'sales_price' => 1000,
            'purchase_price' => 500,
            'tax_rate' => 17.5,
            'is_taxable' => true,
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
            'is_active' => true,
        ]);

        $this->terminal = EisTerminal::create([
            'company_id' => $this->company->id,
            'site_id' => 'SITE-001',
            'status' => EisTerminal::STATUS_ACTIVE,
            'jwt_token' => 'test-jwt-token',
            'secret_key' => 'test-secret-key-here',
            'validation_key' => 'test-validation-key',
            'activated_at' => now(),
        ]);

        $this->posTerminal = \App\Models\PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Main Terminal',
            'identifier' => 'TERM-001',
            'is_active' => true,
        ]);
    }

    public function test_activate_terminal_success(): void
    {
        $terminal = EisTerminal::create([
            'company_id' => $this->company->id,
            'site_id' => 'SITE-002',
            'device_serial' => 'SN-12345',
            'status' => EisTerminal::STATUS_PENDING,
        ]);

        Http::fake([
            '*/api/v1/terminal/activate' => Http::response([
                'statusCode' => 200,
                'jwt_token' => 'real-jwt-token',
                'secret_key' => 'real-secret-key',
                'validation_key' => 'real-validation-key',
            ], 200),
        ]);

        $result = $this->service->activateTerminal($terminal, 'TAC123');

        $terminal->refresh();
        $this->assertEquals(EisTerminal::STATUS_ACTIVE, $terminal->status);
        $this->assertEquals('real-jwt-token', $terminal->jwt_token);
        $this->assertEquals('real-secret-key', $terminal->secret_key);
        $this->assertNotNull($terminal->activated_at);
    }

    public function test_activate_terminal_failure(): void
    {
        $terminal = EisTerminal::create([
            'company_id' => $this->company->id,
            'site_id' => 'SITE-003',
            'status' => EisTerminal::STATUS_PENDING,
        ]);

        Http::fake([
            '*/api/v1/terminal/activate' => Http::response([
                'statusCode' => 400,
                'message' => 'Invalid TAC',
            ], 200),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->activateTerminal($terminal, 'WRONG-TAC');
    }

    public function test_submit_invoice_to_eis(): void
    {
        $sale = $this->createPosSale();

        Http::fake([
            '*/api/v1/sales/submit-sales-transaction' => Http::response([
                'statusCode' => 200,
                'validationURL' => 'https://eis.mra.mw/validate/ABC123',
            ], 200),
        ]);

        $submission = $this->service->submitInvoice($this->terminal, $sale);

        $this->assertEquals(EisSubmission::STATUS_ACCEPTED, $submission->status);
        $this->assertNotNull($submission->validation_url);
        $this->assertEquals($sale->sale_number, $submission->receipt_number);
        $this->assertEquals('B2C', $submission->invoice_type);
    }

    public function test_submit_invoice_rejected(): void
    {
        $sale = $this->createPosSale();

        Http::fake([
            '*/api/v1/sales/submit-sales-transaction' => Http::response([
                'statusCode' => 422,
                'validationErrors' => ['Invalid product code'],
                'shouldBlockTerminal' => false,
            ], 200),
        ]);

        $submission = $this->service->submitInvoice($this->terminal, $sale);

        $this->assertEquals(EisSubmission::STATUS_REJECTED, $submission->status);
        $this->assertEquals('Invalid product code', $submission->error_message);
    }

    public function test_submit_invoice_blocks_terminal(): void
    {
        $sale = $this->createPosSale();

        Http::fake([
            '*/api/v1/sales/submit-sales-transaction' => Http::response([
                'statusCode' => 422,
                'validationErrors' => ['TIN mismatch'],
                'shouldBlockTerminal' => true,
            ], 200),
        ]);

        $this->service->submitInvoice($this->terminal, $sale);

        $this->terminal->refresh();
        $this->assertTrue($this->terminal->should_block_terminal);
    }

    public function test_submit_b2b_with_customer_tin(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Big Corp',
            'tin' => '87654321',
            'is_active' => true,
        ]);

        $sale = $this->createPosSale($customer->id);

        Http::fake([
            '*/api/v1/sales/submit-sales-transaction' => Http::response([
                'statusCode' => 200,
                'validationURL' => 'https://eis.mra.mw/validate/B2B123',
            ], 200),
        ]);

        $submission = $this->service->submitInvoice($this->terminal, $sale);

        $this->assertEquals('B2B', $submission->invoice_type);
        $this->assertEquals(EisSubmission::STATUS_ACCEPTED, $submission->status);

        $payload = $submission->request_payload;
        $this->assertEquals('87654321', $payload['invoiceHeader']['buyerTin']);
    }

    public function test_build_payload_structure(): void
    {
        $sale = $this->createPosSale();

        $payload = $this->service->buildPayload($this->terminal, $sale);

        $this->assertArrayHasKey('invoiceHeader', $payload);
        $this->assertArrayHasKey('invoiceLineItems', $payload);
        $this->assertArrayHasKey('invoiceSummary', $payload);

        $this->assertEquals($this->company->tax_id, $payload['invoiceHeader']['tin']);
        $this->assertEquals('SITE-001', $payload['invoiceHeader']['siteId']);
        $this->assertArrayHasKey('productCode', $payload['invoiceLineItems'][0]);
        $this->assertArrayHasKey('totalVAT', $payload['invoiceSummary']);
    }

    public function test_sign_payload_produces_valid_hmac(): void
    {
        $payload = ['test' => 'data'];
        $secretKey = 'my-secret';

        $signature = $this->service->signPayload($payload, $secretKey);

        $this->assertNotEmpty($signature);

        $expected = base64_encode(hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_UNICODE), $secretKey, true));
        $this->assertEquals($expected, $signature);
    }

    public function test_retry_submission(): void
    {
        $sale = $this->createPosSale();

        $submission = EisSubmission::create([
            'company_id' => $this->company->id,
            'eis_terminal_id' => $this->terminal->id,
            'receipt_number' => $sale->sale_number,
            'invoice_type' => 'B2C',
            'status' => EisSubmission::STATUS_ERROR,
            'request_payload' => $this->service->buildPayload($this->terminal, $sale),
            'error_message' => 'Connection timeout',
            'retry_count' => 0,
        ]);

        Http::fake([
            '*/api/v1/sales/submit-sales-transaction' => Http::response([
                'statusCode' => 200,
                'validationURL' => 'https://eis.mra.mw/validate/RETRY123',
            ], 200),
        ]);

        $result = $this->service->retrySubmission($submission);

        $this->assertEquals(EisSubmission::STATUS_ACCEPTED, $result->status);
        $this->assertEquals(1, $result->retry_count);
        $this->assertNotNull($result->validation_url);
    }

    public function test_retry_max_limit_reached(): void
    {
        $sale = $this->createPosSale();

        $submission = EisSubmission::create([
            'company_id' => $this->company->id,
            'eis_terminal_id' => $this->terminal->id,
            'receipt_number' => $sale->sale_number,
            'invoice_type' => 'B2C',
            'status' => EisSubmission::STATUS_ERROR,
            'request_payload' => $this->service->buildPayload($this->terminal, $sale),
            'retry_count' => 5,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->retrySubmission($submission);
    }

    public function test_retry_only_errors(): void
    {
        $submission = EisSubmission::create([
            'company_id' => $this->company->id,
            'eis_terminal_id' => $this->terminal->id,
            'receipt_number' => 'SALE-001',
            'invoice_type' => 'B2C',
            'status' => EisSubmission::STATUS_ACCEPTED,
            'request_payload' => [],
            'retry_count' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->retrySubmission($submission);
    }

    public function test_cannot_submit_to_inactive_terminal(): void
    {
        $this->terminal->update(['status' => EisTerminal::STATUS_SUSPENDED]);
        $sale = $this->createPosSale();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->submitInvoice($this->terminal, $sale);
    }

    public function test_eis_terminal_is_active_checks_block(): void
    {
        $this->terminal->update(['should_block_terminal' => true]);
        $this->assertFalse($this->terminal->isActive());

        $this->terminal->update(['should_block_terminal' => false]);
        $this->assertTrue($this->terminal->isActive());
    }

    protected function createPosSale(?int $customerId = null): \App\Models\PosSale
    {
        $sale = \App\Models\PosSale::create([
            'company_id' => $this->company->id,
            'terminal_id' => $this->posTerminal->id,
            'cashier_session_id' => null,
            'customer_id' => $customerId,
            'sale_number' => 'POS-SALE-' . strtoupper(uniqid()),
            'subtotal' => 10000,
            'discount_total' => 0,
            'tax_total' => 1750,
            'total' => 11750,
            'status' => \App\Models\PosSale::STATUS_POSTED,
        ]);

        \App\Models\PosSaleLine::create([
            'pos_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 1000,
            'discount_amount' => 0,
            'tax_rate' => 17.5,
            'tax_amount' => 1750,
            'line_total' => 11750,
        ]);

        return $sale;
    }
}
