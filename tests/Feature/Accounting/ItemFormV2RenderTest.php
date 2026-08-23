<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\Account;
use App\Models\Vendor;
use App\Models\Branch;
use App\Models\Company;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use function setPermissionsTeamId;

class ItemFormV2RenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'INVFM',
            'name' => 'Item Form V2 Test',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'inventory');

        $cid = $this->company->id;

        ItemCategory::create(['company_id' => $cid, 'name' => 'Beverages', 'code' => 'BEV']);
        ItemCategory::create(['company_id' => $cid, 'name' => 'Packaging', 'code' => 'PKG']);

        Account::create(['company_id' => $cid, 'code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'sub_type' => 'income', 'is_active' => true]);
        Account::create(['company_id' => $cid, 'code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true]);
        Account::create(['company_id' => $cid, 'code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);
        Account::create(['company_id' => $cid, 'code' => '1320', 'name' => 'Returnable Containers', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true]);

        Vendor::create(['company_id' => $cid, 'name' => 'AHL Group', 'is_active' => true]);
        Branch::create(['company_id' => $cid, 'name' => 'Main Warehouse', 'code' => 'WH01', 'is_active' => true]);
    }

    public function test_items_create_renders_all_sections(): void
    {
        $r = $this->actingAs($this->user)->get(route('accounting.inventory.items.create'));
        $r->assertStatus(200);

        $r->assertSee('Basic Information');
        $r->assertSee('Item Name');
        $r->assertSee('Item Code / SKU');
        $r->assertSee('Barcode / QR');
        $r->assertSee('Track Inventory');
        $r->assertSee('Is Returnable');

        $r->assertSee('Pricing &amp; GL', false);
        $r->assertSee('Purchase Price');
        $r->assertSee('Sales Price');
        $r->assertSee('Margin');
        $r->assertSee('Income Account');
        $r->assertSee('Expense Account');
        $r->assertSee('Inventory Account');

        $r->assertSee('Stock &amp; Reordering', false);
        $r->assertSee('Opening Stock');
        $r->assertSee('Warehouse');
        $r->assertSee('Costing Method');
        $r->assertSee('Default Supplier');

        $r->assertSee('Returnable Parameters');
        $r->assertSee('Container Type');
        $r->assertSee('Deposit Value');
        $r->assertSee('Linked Empty Container');
        $r->assertSee('Container Stock Account');

        $r->assertSee('Active');
        $r->assertSee('Scan QR / Barcode');
        $r->assertSee('itemForm()');
    }

    public function test_items_create_has_account_options(): void
    {
        $r = $this->actingAs($this->user)->get(route('accounting.inventory.items.create'));
        $r->assertStatus(200);

        $r->assertSee('Sales Revenue');
        $r->assertSee('Cost of Goods Sold');
        $r->assertSee('AHL Group');
        $r->assertSee('Main Warehouse');
        $r->assertSee('Beverages');
    }

    public function test_items_edit_renders_with_product(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => 'goods',
            'sales_price' => 500,
            'purchase_price' => 300,
            'income_account_id' => Account::where('company_id', $this->company->id)->where('sub_type', 'income')->first()->id,
            'expense_account_id' => Account::where('company_id', $this->company->id)->where('sub_type', 'cost_of_goods_sold')->first()->id,
            'tracked_as_inventory' => true,
            'is_active' => true,
        ]);

        $r = $this->actingAs($this->user)->get(route('accounting.inventory.items.edit', $product));
        $r->assertStatus(200);

        $r->assertSee('Edit Item');
        $r->assertSee('TEST-001');
        $r->assertSee('Test Product');
        $r->assertSee('Save Changes');
    }

    public function test_items_create_scanner_overlay(): void
    {
        $r = $this->actingAs($this->user)->get(route('accounting.inventory.items.create'));
        $r->assertStatus(200);
        $r->assertSee('inv-scanner-overlay');
        $r->assertSee('Start Camera');
    }

    public function test_items_create_actionbar_and_crumbs(): void
    {
        $r = $this->actingAs($this->user)->get(route('accounting.inventory.items.create'));
        $r->assertStatus(200);
        $r->assertSee('inv-item-form');
        $r->assertSee('inv-sticky-head');
        $r->assertSee('inv-actionbar');
        $r->assertSee('inv-crumbs');
        $r->assertSee('Add Item');
        $r->assertSee('Save &amp; Add Another', false);
    }

    public function test_items_create_gl_note(): void
    {
        $r = $this->actingAs($this->user)->get(route('accounting.inventory.items.create'));
        $r->assertStatus(200);
        $r->assertSee('1320 Returnable Containers');
        $r->assertSee('2300 Customer Bottle Credits');
    }
}
