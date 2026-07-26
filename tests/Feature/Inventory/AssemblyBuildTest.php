<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BillOfMaterial;
use App\Models\BillOfMaterialLine;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Inventory\AssemblyBuildService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssemblyBuildTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Account $assetAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Assembly Test Co',
            'company_code' => 'ATC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '5000'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]
        );
        $this->assetAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '1200'],
            ['name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '6700'],
            ['name' => 'Inventory Adjustment', 'type' => 'expense', 'sub_type' => 'operating_expense', 'is_active' => true]
        );
        Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '6850'],
            ['name' => 'Inventory Count Variance', 'type' => 'expense', 'sub_type' => 'operating_expense', 'is_active' => true]
        );
    }

    private function createProducts(): array
    {
        $comp1 = Product::create([
            'company_id' => $this->company->id, 'name' => 'Widget A', 'sku' => 'WA-001',
            'type' => 'product', 'sales_price' => 10, 'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'inventory_asset_account_id' => $this->assetAccount->id, 'tracked_as_inventory' => true, 'is_active' => true,
        ]);
        $comp2 = Product::create([
            'company_id' => $this->company->id, 'name' => 'Widget B', 'sku' => 'WB-001',
            'type' => 'product', 'sales_price' => 20, 'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'inventory_asset_account_id' => $this->assetAccount->id, 'tracked_as_inventory' => true, 'is_active' => true,
        ]);
        $assembly = Product::create([
            'company_id' => $this->company->id, 'name' => 'Assembly Kit', 'sku' => 'AK-001',
            'type' => 'product', 'sales_price' => 50, 'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'inventory_asset_account_id' => $this->assetAccount->id, 'tracked_as_inventory' => true, 'is_assembly' => true, 'is_active' => true,
        ]);

        return compact('comp1', 'comp2', 'assembly');
    }

    private function setupStock(array $products, InventoryService $inventoryService): void
    {
        $inventoryService->receiveStock($this->company->id, $products['comp1']->id, null, 100, 5.00, 'test', 0, now()->toDateString());
        $inventoryService->receiveStock($this->company->id, $products['comp2']->id, null, 100, 8.00, 'test', 0, now()->toDateString());
    }

    private function createBom(array $products): BillOfMaterial
    {
        $bom = BillOfMaterial::create([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_number' => 'BOM-001',
            'name' => 'Standard Kit',
            'is_active' => true,
        ]);

        BillOfMaterialLine::create(['bom_id' => $bom->id, 'component_product_id' => $products['comp1']->id, 'quantity' => 2]);
        BillOfMaterialLine::create(['bom_id' => $bom->id, 'component_product_id' => $products['comp2']->id, 'quantity' => 1]);

        return $bom;
    }

    public function test_build_creates_assembly_stock(): void
    {
        $products = $this->createProducts();
        $inventoryService = app(InventoryService::class);
        $this->setupStock($products, $inventoryService);
        $bom = $this->createBom($products);

        $service = app(AssemblyBuildService::class);
        $build = $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 5,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $this->assertEquals('build', $build->type);
        $this->assertEquals(5, $build->quantity);
        $this->assertEquals('completed', $build->status);
        $this->assertGreaterThan(0, $build->total_component_cost);
        $this->assertGreaterThan(0, $build->unit_cost);

        $assemblyOnHand = $inventoryService->getQuantityOnHand($this->company->id, $products['assembly']->id);
        $this->assertEquals(5, $assemblyOnHand);

        $comp1OnHand = $inventoryService->getQuantityOnHand($this->company->id, $products['comp1']->id);
        $this->assertEquals(90, $comp1OnHand);

        $comp2OnHand = $inventoryService->getQuantityOnHand($this->company->id, $products['comp2']->id);
        $this->assertEquals(95, $comp2OnHand);
    }

    public function test_unbuild_returns_components(): void
    {
        $products = $this->createProducts();
        $inventoryService = app(InventoryService::class);
        $this->setupStock($products, $inventoryService);
        $bom = $this->createBom($products);

        $service = app(AssemblyBuildService::class);
        $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 5,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $unbuild = $service->unbuild([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 2,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $this->assertEquals('unbuild', $unbuild->type);
        $this->assertEquals(2, $unbuild->quantity);

        $assemblyOnHand = $inventoryService->getQuantityOnHand($this->company->id, $products['assembly']->id);
        $this->assertEquals(3, $assemblyOnHand);
    }

    public function test_build_insufficient_stock_throws(): void
    {
        $products = $this->createProducts();
        $bom = $this->createBom($products);

        $service = app(AssemblyBuildService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 10,
            'date' => now()->toDateString(),
        ], $this->user->id);
    }

    public function test_build_non_assembly_product_throws(): void
    {
        $products = $this->createProducts();
        $products['assembly']->update(['is_assembly' => false]);

        $service = app(AssemblyBuildService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'quantity' => 1,
            'date' => now()->toDateString(),
        ], $this->user->id);
    }

    public function test_build_generates_journal_entry(): void
    {
        $products = $this->createProducts();
        $inventoryService = app(InventoryService::class);
        $this->setupStock($products, $inventoryService);
        $bom = $this->createBom($products);

        $service = app(AssemblyBuildService::class);
        $build = $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 5,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $this->assertNotNull($build->journal_entry_id);
        $this->assertDatabaseHas('journal_entries', ['id' => $build->journal_entry_id]);
    }

    public function test_build_number_is_sequential(): void
    {
        $products = $this->createProducts();
        $inventoryService = app(InventoryService::class);
        $this->setupStock($products, $inventoryService);
        $bom = $this->createBom($products);

        $service = app(AssemblyBuildService::class);
        $build1 = $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 1,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $build2 = $service->build([
            'company_id' => $this->company->id,
            'assembly_product_id' => $products['assembly']->id,
            'bom_id' => $bom->id,
            'quantity' => 1,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $this->assertNotEquals($build1->build_number, $build2->build_number);
        $this->assertStringContainsString('BLD-', $build1->build_number);
        $this->assertStringContainsString('BLD-', $build2->build_number);
    }

    public function test_build_index_route(): void
    {
        $response = $this->get(route('accounting.assemblies.index'));
        $response->assertStatus(200);
    }

    public function test_boms_index_route(): void
    {
        $response = $this->get(route('accounting.assemblies.boms'));
        $response->assertStatus(200);
    }

    public function test_build_history_route(): void
    {
        $response = $this->get(route('accounting.assemblies.history'));
        $response->assertStatus(200);
    }
}
