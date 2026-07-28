<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\Accounting\InventoryService;
use App\Services\Inventory\StockCountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Account $assetAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Count Test Co',
            'company_code' => 'CTC',
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

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'inventory_asset' => '1200',
            'inventory_count_variance' => '6850',
        ];
        foreach ($mappingData as $key => $code) {
            if (isset($accounts[$code])) {
                \App\Models\DefaultAccountMapping::setMapping(
                    $this->company->id,
                    $key,
                    $accounts[$code]->id
                );
            }
        }
    }

    private function createProduct(): Product
    {
        return Product::create([
            'company_id' => $this->company->id,
            'name' => 'Countable Item',
            'sku' => 'CI-001',
            'type' => 'product',
            'sales_price' => 25,
            'income_account_id' => Account::where('company_id', $this->company->id)->where('code', '4000')->first()->id,
            'inventory_asset_account_id' => $this->assetAccount->id,
            'tracked_as_inventory' => true,
            'is_active' => true,
        ]);
    }

    public function test_create_count_populates_expected_quantities(): void
    {
        $product = $this->createProduct();
        $inventoryService = app(InventoryService::class);
        $inventoryService->receiveStock($this->company->id, $product->id, null, 50, 10.00, 'test', 0, now()->toDateString());

        $service = app(StockCountService::class);
        $count = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $this->assertEquals('in_progress', $count->status);
        $this->assertEquals(1, $count->lines->count());

        $line = $count->lines->first();
        $this->assertEquals($product->id, $line->product_id);
        $this->assertEquals(50, $line->expected_quantity);
    }

    public function test_update_count_lines(): void
    {
        $product = $this->createProduct();
        $inventoryService = app(InventoryService::class);
        $inventoryService->receiveStock($this->company->id, $product->id, null, 50, 10.00, 'test', 0, now()->toDateString());

        $service = app(StockCountService::class);
        $count = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $lineId = $count->lines->first()->id;
        $count = $service->updateCountLines($count, [$lineId => 48]);

        $line = $count->lines->first();
        $this->assertEquals(48, $line->counted_quantity);
        $this->assertEquals(-2, $line->variance_quantity);
        $this->assertEquals(20.00, $line->variance_cost);
    }

    public function test_post_count_adjusts_stock(): void
    {
        $product = $this->createProduct();
        $inventoryService = app(InventoryService::class);
        $inventoryService->receiveStock($this->company->id, $product->id, null, 50, 10.00, 'test', 0, now()->toDateString());

        $service = app(StockCountService::class);
        $count = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $lineId = $count->lines->first()->id;
        $service->updateCountLines($count, [$lineId => 47]);
        $posted = $service->postCount($count->fresh(), $this->user->id);

        $this->assertEquals('posted', $posted->status);
        $this->assertNotNull($posted->journal_entry_id);

        $onHand = $inventoryService->getQuantityOnHand($this->company->id, $product->id);
        $this->assertEquals(47, $onHand);
    }

    public function test_post_count_with_increase(): void
    {
        $product = $this->createProduct();
        $inventoryService = app(InventoryService::class);
        $inventoryService->receiveStock($this->company->id, $product->id, null, 50, 10.00, 'test', 0, now()->toDateString());

        $service = app(StockCountService::class);
        $count = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $lineId = $count->lines->first()->id;
        $service->updateCountLines($count, [$lineId => 55]);
        $posted = $service->postCount($count->fresh(), $this->user->id);

        $this->assertEquals('posted', $posted->status);

        $onHand = $inventoryService->getQuantityOnHand($this->company->id, $product->id);
        $this->assertEquals(55, $onHand);
    }

    public function test_cannot_update_posted_count(): void
    {
        $product = $this->createProduct();
        $inventoryService = app(InventoryService::class);
        $inventoryService->receiveStock($this->company->id, $product->id, null, 50, 10.00, 'test', 0, now()->toDateString());

        $service = app(StockCountService::class);
        $count = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $lineId = $count->lines->first()->id;
        $service->updateCountLines($count, [$lineId => 47]);
        $service->postCount($count->fresh(), $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $service->updateCountLines($count->fresh(), [$lineId => 50]);
    }

    public function test_count_number_is_sequential(): void
    {
        $service = app(StockCountService::class);
        $count1 = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $count2 = $service->createCount([
            'company_id' => $this->company->id,
            'date' => now()->toDateString(),
        ], $this->user->id);

        $this->assertNotEquals($count1->count_number, $count2->count_number);
        $this->assertStringContainsString('CNT-', $count1->count_number);
    }

    public function test_count_index_route(): void
    {
        $response = $this->get(route('accounting.stock-counts.index'));
        $response->assertStatus(200);
    }
}
