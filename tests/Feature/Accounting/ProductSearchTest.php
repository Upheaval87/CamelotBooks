<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    private array $defaultProduct = [
        'sales_price' => 10.00,
        'tax_rate' => 0,
        'is_taxable' => false,
    ];

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge(
            $this->defaultProduct,
            ['income_account_id' => $this->incomeAccount->id],
            $overrides
        ));
    }

    public function test_search_by_name(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'A4 Notebook',
            'sku' => 'NB-001',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Stapler',
            'sku' => 'ST-001',
            'type' => 'non_inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => 'notebook']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('A4 Notebook', $data[0]['name']);
    }

    public function test_search_by_sku(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'WDG-42',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => 'WDG']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('WDG-42', $data[0]['sku']);
    }

    public function test_search_by_barcode(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Scanner',
            'sku' => 'SC-001',
            'barcode' => '9781234567890',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => '978123']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('9781234567890', $data[0]['barcode']);
    }

    public function test_search_excludes_inactive_products(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Active Item',
            'sku' => 'ACT-001',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Inactive Item',
            'sku' => 'INA-001',
            'type' => 'inventory',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => 'Item']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Active Item', $data[0]['name']);
    }

    public function test_search_company_isolation(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Our Product',
            'sku' => 'OP-001',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $otherCompany = Company::create([
            'company_code' => 'OTHERCO',
            'name' => 'Other Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->createProduct([
            'company_id' => $otherCompany->id,
            'name' => 'Their Product',
            'sku' => 'TP-001',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => 'Product']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Our Product', $data[0]['name']);
    }

    public function test_search_returns_stock_qty(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Stocked Item',
            'sku' => 'STK-001',
            'type' => 'inventory',
            'tracked_as_inventory' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => 'Stocked']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('stock_qty', $data[0]);
    }

    public function test_search_empty_query_returns_all_active(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Something',
            'sku' => 'SM-001',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.products.search', ['q' => '']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
    }

    public function test_search_requires_authentication(): void
    {
        $response = $this->getJson(route('accounting.products.search', ['q' => 'test']));
        $response->assertUnauthorized();
    }
}
