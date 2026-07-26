<?php

namespace Tests\Feature\Inventory;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\ItemCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Account $incomeAccount;
    private Account $cogsAccount;
    private Account $assetAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Category Test Co',
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

        $this->incomeAccount = Account::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => '4000'],
            ['name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue', 'is_active' => true]
        );
        $this->cogsAccount = Account::firstOrCreate(
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

    private function createCategory(array $overrides = []): ItemCategory
    {
        return ItemCategory::create(array_merge([
            'company_id' => $this->company->id,
            'code' => 'ELEC',
            'name' => 'Electronics',
            'default_income_account_id' => $this->incomeAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
            'default_inventory_asset_account_id' => $this->assetAccount->id,
            'default_reorder_point' => 10,
            'default_base_uom' => 'Each',
            'is_active' => true,
        ], $overrides));
    }

    public function test_create_category(): void
    {
        $service = app(ItemCategoryService::class);

        $category = $service->create([
            'company_id' => $this->company->id,
            'code' => 'FURN',
            'name' => 'Furniture',
            'default_income_account_id' => $this->incomeAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
            'default_inventory_asset_account_id' => $this->assetAccount->id,
        ], $this->company->id);

        $this->assertNotNull($category);
        $this->assertEquals('FURN', $category->code);
        $this->assertEquals('Furniture', $category->name);
        $this->assertEquals($this->incomeAccount->id, $category->default_income_account_id);
    }

    public function test_duplicate_code_rejected(): void
    {
        $this->createCategory(['code' => 'ELEC']);

        $service = app(ItemCategoryService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->create([
            'company_id' => $this->company->id,
            'code' => 'ELEC',
            'name' => 'Duplicate',
        ], $this->company->id);
    }

    public function test_update_category(): void
    {
        $category = $this->createCategory();
        $service = app(ItemCategoryService::class);

        $updated = $service->update($category, [
            'code' => 'ELEC',
            'name' => 'Electronics & Gadgets',
            'default_reorder_point' => 25,
        ]);

        $this->assertEquals('Electronics & Gadgets', $updated->name);
        $this->assertEquals(25, $updated->default_reorder_point);
    }

    public function test_toggle_category(): void
    {
        $category = $this->createCategory(['is_active' => true]);
        $service = app(ItemCategoryService::class);

        $toggled = $service->toggle($category);
        $this->assertFalse($toggled->is_active);

        $toggledAgain = $service->toggle($toggled);
        $this->assertTrue($toggledAgain->is_active);
    }

    public function test_get_category_defaults(): void
    {
        $category = $this->createCategory();
        $service = app(ItemCategoryService::class);

        $defaults = $service->getCategoryDefaults($category->id);

        $this->assertNotNull($defaults);
        $this->assertEquals($this->incomeAccount->id, $defaults['income_account_id']);
        $this->assertEquals($this->cogsAccount->id, $defaults['cogs_account_id']);
        $this->assertEquals($this->assetAccount->id, $defaults['inventory_asset_account_id']);
        $this->assertEquals(10, $defaults['reorder_point']);
        $this->assertEquals('Each', $defaults['unit_of_measure']);
    }

    public function test_product_inherits_category_defaults(): void
    {
        $category = $this->createCategory();

        $product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'Laptop',
            'type' => 'product',
            'sales_price' => 999.99,
            'income_account_id' => $this->incomeAccount->id,
            'tracked_as_inventory' => true,
        ]);

        $this->assertEquals($this->incomeAccount->id, $product->effective_income_account_id);
        $this->assertEquals($this->cogsAccount->id, $product->effective_cogs_account_id);
        $this->assertEquals($this->assetAccount->id, $product->effective_inventory_asset_account_id);
        $this->assertEquals(10, $product->effective_reorder_point);
        $this->assertEquals('Each', $product->effective_base_uom);
    }

    public function test_product_overrides_category_defaults(): void
    {
        $category = $this->createCategory();

        $product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'Laptop',
            'type' => 'product',
            'sales_price' => 999.99,
            'income_account_id' => $this->incomeAccount->id,
            'expense_account_id' => $this->cogsAccount->id,
            'reorder_point' => 50,
            'tracked_as_inventory' => true,
        ]);

        $this->assertEquals($this->incomeAccount->id, $product->effective_income_account_id);
        $this->assertEquals($this->cogsAccount->id, $product->effective_cogs_account_id);
        $this->assertEquals(50, $product->effective_reorder_point);
    }

    public function test_index_route(): void
    {
        $this->createCategory();

        $response = $this->get(route('accounting.item-categories.index'));
        $response->assertStatus(200);
        $response->assertSee('Electronics');
    }

    public function test_create_route(): void
    {
        $response = $this->get(route('accounting.item-categories.create'));
        $response->assertStatus(200);
        $response->assertSee('New Item Category');
    }

    public function test_store_category_via_controller(): void
    {
        $response = $this->post(route('accounting.item-categories.store'), [
            'code' => 'NEW',
            'name' => 'New Category',
            'default_income_account_id' => $this->incomeAccount->id,
            'default_cogs_account_id' => $this->cogsAccount->id,
            'default_inventory_asset_account_id' => $this->assetAccount->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('item_categories', [
            'company_id' => $this->company->id,
            'code' => 'NEW',
            'name' => 'New Category',
        ]);
    }
}
