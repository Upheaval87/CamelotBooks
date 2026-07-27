<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\Accounting\QuotationService;
use App\Services\Accounting\SalesReceiptService;
use App\Services\Accounting\SalesPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TESTCO',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'allow_negative_stock' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);

        $this->incomeAccount = Account::create(['company_id' => $this->company->id, 'code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'revenue', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '2300', 'name' => 'Tax Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'operating_expense', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1200', 'name' => 'Inventory Asset', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '9999', 'name' => 'Rounding Differences', 'type' => 'expense', 'sub_type' => 'non_operating_expense', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1050', 'name' => 'Undeposited Funds', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1070', 'name' => 'Card Clearing', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);
        Account::create(['company_id' => $this->company->id, 'code' => '1080', 'name' => 'Mobile Money Clearing', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);

        $this->customer = Customer::create(['company_id' => $this->company->id, 'name' => 'Test Customer', 'email' => 'customer@test.com', 'is_active' => true]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TP001',
            'type' => 'inventory',
            'tracked_as_inventory' => true,
            'sales_price' => 100,
            'income_account_id' => $this->incomeAccount->id,
            'is_active' => true,
        ]);

        foreach (['quotation', 'sales_receipt'] as $dt) {
            \App\Models\NumberingSequence::create([
                'company_id' => $this->company->id,
                'document_type' => $dt,
                'prefix' => $dt === 'quotation' ? 'QTN-' : 'SR-',
                'padding_width' => 4,
                'next_number' => 1,
                'reset_policy' => 'annually',
                'is_active' => true,
            ]);
        }

        \App\Models\AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        \App\Models\AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q2',
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);

        \App\Models\AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q3',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'status' => 'open',
        ]);

        \App\Models\AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q4',
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        \App\Models\AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2027 Q1',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'status' => 'open',
        ]);
    }

    public function test_quotation_create_and_show(): void
    {
        $service = app(QuotationService::class);

        $quotation = $service->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount' => 0,
                    'tax_rate' => 17.5,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->user->id);

        $this->assertNotNull($quotation);
        $this->assertEquals('draft', $quotation->status);
        $this->assertStringStartsWith('QTN-', $quotation->quotation_number);
        $this->assertEquals(200, $quotation->amount);
        $this->assertEquals(35, $quotation->tax_total);
        $this->assertEquals(235, $quotation->total);
        $this->assertCount(1, $quotation->lines);
    }

    public function test_quotation_lifecycle(): void
    {
        $service = app(QuotationService::class);

        $quotation = $service->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->user->id);

        $quotation = $service->send($quotation);
        $this->assertEquals('sent', $quotation->status);

        $quotation = $service->accept($quotation);
        $this->assertEquals('accepted', $quotation->status);

        $quotation = $service->void($quotation, 'Test void', $this->user->id);
        $this->assertEquals('void', $quotation->status);
    }

    public function test_quotation_convert_to_invoice(): void
    {
        $service = app(QuotationService::class);

        $quotation = $service->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->user->id);

        $service->send($quotation);
        $service->accept($quotation);

        $invoice = $service->convertToInvoice($quotation, $this->user->id);

        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);

        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status);
        $this->assertEquals($invoice->id, $quotation->converted_invoice_id);
    }

    public function test_sales_receipt_create_and_post(): void
    {
        $service = app(SalesReceiptService::class);

        $pm = \App\Models\PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => Account::where('company_id', $this->company->id)->where('code', '1000')->first()->id,
            'is_active' => true,
        ]);

        $receipt = $service->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'receipt_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'tax_rate' => 17.5,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $pm->id,
                    'amount' => 235,
                ],
            ],
        ], $this->user->id);

        $this->assertNotNull($receipt);
        $this->assertEquals('draft', $receipt->status);
        $this->assertStringStartsWith('SR-', $receipt->receipt_number);
        $this->assertEquals(235, $receipt->total);

        $receipt = $service->post($receipt, $this->user->id);
        $this->assertEquals('posted', $receipt->status);
        $this->assertNotNull($receipt->journal_entry_id);
        $this->assertNotNull($receipt->posted_at);
    }

    public function test_sales_receipt_void_reverses_journal(): void
    {
        $service = app(SalesReceiptService::class);

        $pm = \App\Models\PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => Account::where('company_id', $this->company->id)->where('code', '1000')->first()->id,
            'is_active' => true,
        ]);

        $receipt = $service->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'receipt_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 17.5,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $pm->id,
                    'amount' => 117.50,
                ],
            ],
        ], $this->user->id);

        $receipt = $service->post($receipt, $this->user->id);
        $originalJeId = $receipt->journal_entry_id;

        $receipt = $service->void($receipt, 'Test void', $this->user->id);
        $this->assertEquals('voided', $receipt->status);

        $originalJe = \App\Models\JournalEntry::find($originalJeId);
        $this->assertEquals('reversed', $originalJe->status);

        $reversal = \App\Models\JournalEntry::where('linked_entry_id', $originalJeId)->first();
        $this->assertNotNull($reversal);
    }

    public function test_sales_posting_service(): void
    {
        $service = app(SalesPostingService::class);

        $pm = \App\Models\PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => Account::where('company_id', $this->company->id)->where('code', '1000')->first()->id,
            'is_active' => true,
        ]);

        $je = $service->postSale([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'source_module' => 'sales_receipt',
            'document_number' => 'SR-TEST-001',
            'date' => now()->toDateString(),
            'memo' => 'Test posting',
            'lines' => [
                [
                    'product_name' => 'Test Product',
                    'income_account_id' => $this->incomeAccount->id,
                    'line_total' => 117.50,
                    'tax_amount' => 17.50,
                    'cost_of_goods' => 0,
                    'tracked_as_inventory' => false,
                ],
            ],
            'payments' => [
                [
                    'amount' => 117.50,
                    'payment_method_name' => 'Cash',
                    'clearing_account_id' => $pm->clearing_account_id,
                    'bank_account_id' => null,
                ],
            ],
        ]);

        $this->assertNotNull($je);
        $this->assertEquals('posted', $je->status);

        $totalDebits = $je->lines->sum('debit');
        $totalCredits = $je->lines->sum('credit');
        $this->assertEqualsWithDelta($totalDebits, $totalCredits, 0.01);
    }

    public function test_quotation_update_recalculates_totals(): void
    {
        $service = app(QuotationService::class);

        $quotation = $service->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->user->id);

        $this->assertEquals(100, $quotation->total);

        $quotation = $service->update($quotation, [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->toDateString(),
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product x3',
                    'quantity' => 3,
                    'unit_price' => 50,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ]);

        $this->assertEquals(150, $quotation->total);
        $this->assertEquals('Test Product x3', $quotation->lines->first()->description);
    }
}
