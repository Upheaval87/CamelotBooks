<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\ItemUomConversion;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosTerminal;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use App\Services\Accounting\InventoryService;
use App\Services\FeatureManagement;
use App\Services\Inventory\UnitOfMeasureConversionService;
use App\Services\POS\PosSaleService;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOfMeasureTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Account $revenueAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'UOM Test Co',
            'company_code' => 'UTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'pos');
        FeatureManagement::enable($this->company->id, 'inventory');
        PosSetupService::seedDefaultsForCompany($this->company->id);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        $this->revenueAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '5000'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1200'],
            ['name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2300'],
            ['name' => 'Sales Tax Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '2000'],
            ['name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true]
        );

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'pos_sale',
            'prefix' => 'POS-',
            'padding_width' => 5,
            'reset_policy' => 'never',
            'next_number' => 1,
            'is_active' => true,
        ]);

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'bill',
            'prefix' => 'BILL-',
            'padding_width' => 5,
            'reset_policy' => 'never',
            'next_number' => 1,
            'is_active' => true,
        ]);
    }

    // =============================================
    // SERVICE TESTS
    // =============================================

    public function test_convert_to_base_uses_correct_factor(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda',
            'sku' => 'SODA-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 1.50,
            'purchase_price' => 0.75,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Piece',
            'income_account_id' => $this->revenueAccount->id,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Piece',
            'conversion_factor' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Carton',
            'conversion_factor' => 24.0,
            'is_base' => false,
            'is_active' => true,
        ]);

        $service = app(UnitOfMeasureConversionService::class);

        $result = $service->convertToBase($this->company->id, $product->id, 'Carton', 5);
        $this->assertEquals(120.0, $result['base_qty']);
        $this->assertEquals(24.0, $result['conversion_factor']);
        $this->assertEquals('Carton', $result['transaction_uom']);
        $this->assertEquals(5, $result['transaction_qty']);
    }

    public function test_base_uom_returns_same_qty(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WGT-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Each',
            'income_account_id' => $this->revenueAccount->id,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Each',
            'conversion_factor' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        $service = app(UnitOfMeasureConversionService::class);

        $result = $service->convertToBase($this->company->id, $product->id, 'Each', 10);
        $this->assertEquals(10.0, $result['base_qty']);
        $this->assertEquals(1.0, $result['conversion_factor']);
    }

    public function test_invalid_uom_throws_exception(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Item',
            'sku' => 'ITM-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Each',
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $service = app(UnitOfMeasureConversionService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->convertToBase($this->company->id, $product->id, 'Nonexistent', 10);
    }

    public function test_get_product_uoms_returns_all_active(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda',
            'sku' => 'SODA-002',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 1.50,
            'purchase_price' => 0.75,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Piece',
            'income_account_id' => $this->revenueAccount->id,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Piece',
            'conversion_factor' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Carton',
            'conversion_factor' => 24.0,
            'is_base' => false,
            'is_active' => true,
        ]);

        $service = app(UnitOfMeasureConversionService::class);
        $uoms = $service->getProductUoms($this->company->id, $product->id);

        $this->assertCount(2, $uoms);
        $this->assertTrue($uoms->first()->is_base);
    }

    public function test_ensure_base_uom_creates_default(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'New Item',
            'sku' => 'NEW-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 5.00,
            'purchase_price' => 2.50,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Each',
            'income_account_id' => $this->revenueAccount->id,
        ]);

        $service = app(UnitOfMeasureConversionService::class);
        $base = $service->ensureBaseUom($this->company->id, $product->id);

        $this->assertTrue($base->is_base);
        $this->assertEquals(1.0, $base->conversion_factor);
        $this->assertEquals('Each', $base->uom_name);
    }

    // =============================================
    // PURCHASE (BILL) + INVENTORY INTEGRATION
    // =============================================

    public function test_bill_with_uom_conversion_receives_correct_base_qty(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda Carton',
            'sku' => 'SODA-C',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 1.50,
            'purchase_price' => 18.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Piece',
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
            'inventory_asset_account_id' => Account::where('company_id', $this->company->id)->where('code', '1200')->first()->id,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Piece',
            'conversion_factor' => 1.0,
            'purchase_price' => 1.50,
            'sales_price' => 1.50,
            'is_base' => true,
            'is_active' => true,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Carton',
            'conversion_factor' => 24.0,
            'purchase_price' => 18.00,
            'sales_price' => 30.00,
            'is_base' => false,
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Beverage Distributor',
            'is_active' => true,
        ]);

        $billService = app(BillService::class);
        $bill = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'lines' => [
                [
                    'product_id' => $product->id,
                    'description' => '10 Cartons of Soda',
                    'quantity' => 240,
                    'transaction_uom' => 'Carton',
                    'transaction_qty' => 10,
                    'conversion_factor' => 24.0,
                    'unit_price' => 1.50,
                    'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
                ],
            ],
        ], $this->user->id);

        $line = $bill->lines->first();
        $this->assertEquals('Carton', $line->transaction_uom);
        $this->assertEquals(10, $line->transaction_qty);
        $this->assertEquals(24.0, $line->conversion_factor);
        $this->assertEquals(240, $line->quantity);
        $this->assertEquals(15.00, $line->amount);
    }

    public function test_bill_without_uom_still_works(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Simple Widget',
            'sku' => 'SIM-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Each',
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
            'inventory_asset_account_id' => Account::where('company_id', $this->company->id)->where('code', '1200')->first()->id,
        ]);

        $vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Widget Supplier',
            'is_active' => true,
        ]);

        $billService = app(BillService::class);
        $bill = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'lines' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Simple purchase',
                    'quantity' => 10,
                    'unit_price' => 5.00,
                    'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
                ],
            ],
        ], $this->user->id);

        $line = $bill->lines->first();
        $this->assertNull($line->transaction_uom);
        $this->assertEquals(10, $line->quantity);
        $this->assertEquals(50.00, $line->amount);
    }

    // =============================================
    // POS SALE + INVENTORY INTEGRATION
    // =============================================

    public function test_pos_sale_with_uom_conversion_consumes_correct_base_qty(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Soda Piece',
            'sku' => 'SODA-P',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 1.50,
            'purchase_price' => 0.75,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Piece',
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
            'inventory_asset_account_id' => Account::where('company_id', $this->company->id)->where('code', '1200')->first()->id,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Piece',
            'conversion_factor' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        ItemUomConversion::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'uom_name' => 'Carton',
            'conversion_factor' => 24.0,
            'is_base' => false,
            'is_active' => true,
        ]);

        // Receive stock: 2 cartons = 48 pieces
        app(InventoryService::class)->receiveStock(
            $this->company->id,
            $product->id,
            null,
            48,
            0.75,
            'bill',
            1,
            now()->toDateString()
        );

        $terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'T1',
            'identifier' => 'T1',
            'is_active' => true,
        ]);

        $cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();

        $sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $terminal->id,
            'lines' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 24,
                    'transaction_uom' => 'Carton',
                    'transaction_qty' => 1,
                    'conversion_factor' => 24.0,
                    'unit_price' => 1.50,
                    'tax_rate' => 0,
                ],
            ],
            'payments' => [
                ['payment_method_id' => $cashMethod->id, 'amount' => 1.50],
            ],
        ], $this->user->id);

        $line = $sale->lines->first();
        $this->assertEquals('Carton', $line->transaction_uom);
        $this->assertEquals(1, $line->transaction_qty);
        $this->assertEquals(24.0, $line->conversion_factor);
        $this->assertEquals(24, $line->quantity);
        $this->assertEquals(1.50, $line->line_total);
        $this->assertNotNull($line->cost_of_goods);

        // Verify stock decreased by 24 pieces (1 carton)
        $stock = \App\Models\InventoryStock::where('product_id', $product->id)->first();
        $this->assertEquals(24, $stock->quantity_on_hand);
    }

    public function test_pos_sale_without_uom_still_works(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Simple Sale Item',
            'sku' => 'SSI-001',
            'type' => 'goods',
            'tracked_as_inventory' => true,
            'sales_price' => 10.00,
            'purchase_price' => 5.00,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Each',
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('code', '5000')->first()->id,
            'inventory_asset_account_id' => Account::where('company_id', $this->company->id)->where('code', '1200')->first()->id,
        ]);

        app(InventoryService::class)->receiveStock(
            $this->company->id,
            $product->id,
            null,
            10,
            5.00,
            'bill',
            1,
            now()->toDateString()
        );

        $terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'T2',
            'identifier' => 'T2',
            'is_active' => true,
        ]);

        $cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();

        $sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $terminal->id,
            'lines' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 10.00,
                    'tax_rate' => 0,
                ],
            ],
            'payments' => [
                ['payment_method_id' => $cashMethod->id, 'amount' => 30.00],
            ],
        ], $this->user->id);

        $line = $sale->lines->first();
        $this->assertNull($line->transaction_uom);
        $this->assertEquals(3, $line->quantity);
        $this->assertEquals(30.00, $line->line_total);
    }

    public function test_non_tracked_product_uom_does_not_affect_inventory(): void
    {
        $serviceProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Delivery Service',
            'sku' => 'SVC-001',
            'type' => 'service',
            'tracked_as_inventory' => false,
            'sales_price' => 50.00,
            'purchase_price' => 0,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
            'unit_of_measure' => 'Hour',
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
        ]);

        $terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'T3',
            'identifier' => 'T3',
            'is_active' => true,
        ]);

        $cashMethod = PosPaymentMethod::where('company_id', $this->company->id)->where('name', 'Cash')->first();

        $sale = app(PosSaleService::class)->checkout([
            'company_id' => $this->company->id,
            'terminal_id' => $terminal->id,
            'lines' => [
                [
                    'product_id' => $serviceProduct->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'tax_rate' => 0,
                ],
            ],
            'payments' => [
                ['payment_method_id' => $cashMethod->id, 'amount' => 100.00],
            ],
        ], $this->user->id);

        $line = $sale->lines->first();
        $this->assertNull($line->cost_of_goods);
        $this->assertDatabaseCount('inventory_stock', 0);
    }
}
