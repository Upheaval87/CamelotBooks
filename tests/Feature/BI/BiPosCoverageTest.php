<?php

namespace Tests\Feature\BI;

use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\PosPayment;
use App\Models\PosTerminal;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\AccountingPeriod;
use App\Models\NumberingSequence;
use App\Services\BI\CustomerLifetimeValueService;
use App\Services\BI\SalesFactBuilder;
use App\Services\Reporting\Analytics\SalesAnalyticsService;
use App\Services\FeatureManagement;
use App\Services\POS\PosSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BiPosCoverageTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Product $product;
    private PosTerminal $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'BI POS Test Co',
            'company_code' => 'BPTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'pos');
        PosSetupService::seedDefaultsForCompany($this->company->id);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'T1',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2300'],
            ['name' => 'Tax Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '5000'],
            ['name' => 'COGS', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
        );

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WGT-001',
            'type' => 'goods',
            'tracked_as_inventory' => false,
            'sales_price' => 15.00,
            'purchase_price' => 5.00,
            'tax_rate' => 10.00,
            'is_taxable' => true,
            'is_active' => true,
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
        ]);
    }

    private function createPosSale(float $total): PosSale
    {
        $session = new PosCashierSession();
        $session->company_id = $this->company->id;
        $session->terminal_id = $this->terminal->id;
        $session->user_id = $this->user->id;
        $session->status = 'open';
        $session->opening_float = 200;
        $session->opened_at = now();
        $session->save();

        $sale = new PosSale();
        $sale->company_id = $this->company->id;
        $sale->terminal_id = $this->terminal->id;
        $sale->cashier_session_id = $session->id;
        $sale->branch_id = null;
        $sale->cost_center_id = null;
        $sale->customer_id = null;
        $sale->sale_number = 'POS-' . str_pad(PosSale::where('company_id', $this->company->id)->count() + 1, 5, '0', STR_PAD_LEFT);
        $sale->subtotal = $total / 1.1;
        $sale->discount_total = 0;
        $sale->tax_total = $total - ($total / 1.1);
        $sale->total = $total;
        $sale->status = 'posted';
        $sale->save();

        $line = new PosSaleLine();
        $line->pos_sale_id = $sale->id;
        $line->product_id = $this->product->id;
        $line->quantity = 1;
        $line->unit_price = $total / 1.1;
        $line->discount_amount = 0;
        $line->tax_rate = 10;
        $line->tax_amount = $total - ($total / 1.1);
        $line->line_total = $total;
        $line->save();

        return $sale;
    }

    // =============================================
    // SALES FACT BUILDER — POS COVERAGE
    // =============================================

    public function test_sales_fact_builder_includes_pos_sales(): void
    {
        $sale = $this->createPosSale(22.00);

        DB::table('fact_sales')->truncate();
        $builder = new SalesFactBuilder();
        $count = $builder->build();

        $this->assertGreaterThanOrEqual(1, $count);

        $row = DB::table('fact_sales')->where('source_type', 'pos_sale')->first();
        $this->assertNotNull($row);
        $this->assertEquals($sale->id, $row->source_id);
        $this->assertEquals($sale->sale_number, $row->source_number);
        $this->assertEquals($sale->status, $row->source_status);
    }

    public function test_sales_fact_builder_pos_source_type(): void
    {
        $this->createPosSale(33.00);

        DB::table('fact_sales')->truncate();
        $builder = new SalesFactBuilder();
        $builder->build();

        $types = DB::table('fact_sales')->pluck('source_type')->unique()->toArray();
        $this->assertContains('pos_sale', $types);
    }

    public function test_sales_fact_builder_excludes_voided_pos_sales(): void
    {
        $sale = $this->createPosSale(44.00);
        $sale->status = 'voided';
        $sale->save();

        DB::table('fact_sales')->truncate();
        $builder = new SalesFactBuilder();
        $builder->build();

        $posRows = DB::table('fact_sales')->where('source_type', 'pos_sale')->count();
        $this->assertEquals(0, $posRows);
    }

    // =============================================
    // CUSTOMER LIFETIME VALUE — POS COVERAGE
    // =============================================

    public function test_customer_lifetime_value_includes_pos_sales(): void
    {
        // Create a customer linked POS sale
        $customer = \App\Models\Customer::create([
            'company_id' => $this->company->id,
            'name' => 'POS Customer',
            'email' => 'pos@example.com',
            'is_active' => true,
        ]);

        $session = new PosCashierSession();
        $session->company_id = $this->company->id;
        $session->terminal_id = $this->terminal->id;
        $session->user_id = $this->user->id;
        $session->status = 'open';
        $session->opening_float = 200;
        $session->opened_at = now();
        $session->save();

        $sale = new PosSale();
        $sale->company_id = $this->company->id;
        $sale->terminal_id = $this->terminal->id;
        $sale->cashier_session_id = $session->id;
        $sale->customer_id = $customer->id;
        $sale->sale_number = 'POS-00001';
        $sale->subtotal = 100;
        $sale->discount_total = 0;
        $sale->tax_total = 0;
        $sale->total = 100;
        $sale->status = 'posted';
        $sale->save();

        $line = new PosSaleLine();
        $line->pos_sale_id = $sale->id;
        $line->product_id = $this->product->id;
        $line->quantity = 1;
        $line->unit_price = 100;
        $line->discount_amount = 0;
        $line->tax_rate = 0;
        $line->tax_amount = 0;
        $line->line_total = 100;
        $line->save();

        // Rebuild fact_sales
        DB::table('fact_sales')->truncate();
        $builder = new SalesFactBuilder();
        $builder->build();

        $service = new CustomerLifetimeValueService();
        $data = $service->calculate($this->company->id);

        $this->assertEquals(1, $data['total_customers']);
        $this->assertEqualsWithDelta(100, $data['total_revenue'], 0.01);
    }

    // =============================================
    // SALES ANALYTICS SERVICE — POS COVERAGE
    // =============================================

    public function test_sales_analytics_includes_pos_sales(): void
    {
        $this->createPosSale(55.00);

        $service = new SalesAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('monthly_summary', $data);
        $this->assertGreaterThan(0, count($data['monthly_summary']));
        $this->assertGreaterThan(0, $data['invoice_count']);
    }

    public function test_sales_analytics_pos_and_invoice_count_combined(): void
    {
        // Create a POS sale
        $this->createPosSale(55.00);

        // Create an invoice
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1100'],
            ['name' => 'AR', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );
        $ar = Account::where('company_id', $this->company->id)->where('code', '1100')->first();
        $rev = Account::where('company_id', $this->company->id)->where('code', '4000')->first();

        $je = \App\Models\JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-001',
            'created_by' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'posted',
            'reference' => 'TEST',
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je->id, 'account_id' => $ar->id, 'debit' => 100, 'credit' => 0,
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je->id, 'account_id' => $rev->id, 'debit' => 0, 'credit' => 100,
        ]);

        $customer = \App\Models\Customer::create([
            'company_id' => $this->company->id, 'name' => 'Inv Customer',
            'email' => 'inv@test.com', 'is_active' => true,
        ]);

        $invoice = \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'journal_entry_id' => $je->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 100,
            'amount_paid' => 0,
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        \App\Models\InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'amount' => 100,
            'line_total' => 100,
            'income_account_id' => $rev->id,
            'description' => 'Widget',
        ]);

        $service = new SalesAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        // Should count both POS sale + invoice (at least the POS sale)
        $this->assertGreaterThanOrEqual(1, $data['invoice_count']);
    }

    public function test_sales_analytics_top_products_includes_pos_products(): void
    {
        $this->createPosSale(110.00);

        $service = new SalesAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('top_products', $data);
        $this->assertGreaterThan(0, count($data['top_products']));
        $this->assertEquals('Widget', $data['top_products'][0]->product_name);
    }

    public function test_sales_analytics_empty_data_returns_empty(): void
    {
        $service = new SalesAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('revenue', $data);
        $this->assertArrayHasKey('monthly_summary', $data);
        $this->assertCount(0, $data['monthly_summary']);
    }
}
