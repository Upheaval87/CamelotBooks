<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
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
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
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

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($this->company->id, ['role' => $role]);
        setPermissionsTeamId($this->company->id);
        $user->assignRole($role);

        return $user;
    }

    private function otherCompany(): Company
    {
        return Company::create([
            'company_code' => 'OTHERCO',
            'name' => 'Other Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
    }

    private function createAssetRow(): Asset
    {
        $assetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1500',
            'name' => 'Fixed Assets',
            'type' => 'asset',
            'sub_type' => 'fixed_asset',
            'is_active' => true,
        ]);
        $accumDep = Account::create([
            'company_id' => $this->company->id,
            'code' => '1600',
            'name' => 'Accumulated Depreciation',
            'type' => 'contra_asset',
            'sub_type' => 'accumulated_depreciation',
            'is_active' => true,
        ]);
        $depExp = Account::create([
            'company_id' => $this->company->id,
            'code' => '6200',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'depreciation',
            'is_active' => true,
        ]);

        $category = AssetCategory::create([
            'company_id' => $this->company->id,
            'code' => 'MACH-01',
            'name' => 'Machinery',
            'depreciation_method_financial' => 'straight_line',
            'useful_life_financial' => 60,
            'residual_value_type_financial' => 'amount',
            'residual_value_financial' => 1000,
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'residual_value_type_tax' => 'amount',
            'residual_value_tax' => 1000,
            'is_active' => true,
            'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumDep->id,
            'depreciation_expense_account_id' => $depExp->id,
        ]);

        return Asset::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'asset_code' => 'A-1001',
            'name' => 'Delivery Van',
            'acquisition_date' => '2026-01-01',
            'in_service_date' => '2026-01-01',
            'acquisition_cost' => 10000,
            'useful_life' => 60,
            'depreciation_method_financial' => 'straight_line',
            'depreciation_method_tax' => 'straight_line',
            'useful_life_tax' => 60,
            'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumDep->id,
            'depreciation_expense_account_id' => $depExp->id,
            'is_active' => true,
        ]);
    }

    // ──────────────────────────────────────────────
    // Mode 1: scoped entity search
    // ──────────────────────────────────────────────

    public function test_scoped_search_scopes_to_active_company(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Our Widget',
            'sku' => 'OW-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $other = $this->otherCompany();
        $this->createProduct([
            'company_id' => $other->id,
            'name' => 'Their Widget',
            'sku' => 'TW-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.entity', ['entity' => 'product', 'q' => 'Widget']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Our Widget', $data[0]['label']);
        $this->assertEquals('OW-1', $data[0]['sku']);
        $this->assertArrayHasKey('url', $data[0]);
    }

    public function test_scoped_search_matches_accounts_by_code(): void
    {
        Account::create([
            'company_id' => $this->company->id,
            'code' => '1010',
            'name' => 'Cash on Hand',
            'type' => 'asset',
            'sub_type' => 'cash',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.entity', ['entity' => 'account', 'q' => '101']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Cash on Hand', $data[0]['label']);
        $this->assertStringContainsString('1010', $data[0]['subtitle']);
    }

    public function test_scoped_search_excludes_inactive_records(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Active Widget',
            'sku' => 'AW-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Retired Widget',
            'sku' => 'RW-1',
            'type' => 'inventory',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.entity', ['entity' => 'product', 'q' => 'Widget']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Active Widget', $data[0]['label']);
    }

    public function test_scoped_search_unknown_entity_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.entity', ['entity' => 'does-not-exist', 'q' => 'x']));

        $response->assertNotFound();
    }

    public function test_scoped_search_feature_gate_returns_404_when_disabled(): void
    {
        // fixed_assets feature not enabled for this company
        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.entity', ['entity' => 'asset', 'q' => 'x']));

        $response->assertNotFound();
    }

    public function test_scoped_search_excludes_records_user_cannot_view(): void
    {
        // viewer passes the accounting role gate but lacks fixed-assets.view
        $viewer = $this->makeUser('viewer');
        \App\Services\FeatureManagement::enable($this->company->id, 'fixed_assets');

        $response = $this->actingAs($viewer)
            ->getJson(route('accounting.search.entity', ['entity' => 'asset', 'q' => 'x']));

        $response->assertForbidden();
    }

    public function test_scoped_search_requires_authentication(): void
    {
        $response = $this->getJson(route('accounting.search.entity', ['entity' => 'product', 'q' => 'x']));

        $response->assertUnauthorized();
    }

    // ──────────────────────────────────────────────
    // Mode 2: global search
    // ──────────────────────────────────────────────

    public function test_global_search_groups_results_and_scopes_company(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Our Widget',
            'sku' => 'OW-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $other = $this->otherCompany();
        $this->createProduct([
            'company_id' => $other->id,
            'name' => 'Their Widget',
            'sku' => 'TW-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '1010',
            'name' => 'Cash on Hand',
            'type' => 'asset',
            'sub_type' => 'cash',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.global', ['q' => 'widget']));

        $response->assertOk();
        $groups = $response->json();

        $productGroup = collect($groups)->firstWhere('key', 'product');
        $this->assertNotNull($productGroup);
        $this->assertCount(1, $productGroup['results']);
        $this->assertEquals('Our Widget', $productGroup['results'][0]['title']);
        $this->assertEquals('product', $productGroup['results'][0]['type']);
        $this->assertArrayHasKey('url', $productGroup['results'][0]);

        // Only groups with matches appear
        $this->assertNull(collect($groups)->firstWhere('key', 'account'));
    }

    public function test_global_search_feature_gate_omits_and_includes_entity(): void
    {
        $this->createAssetRow();

        // feature disabled → asset group absent
        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.global', ['q' => 'Van']));
        $response->assertOk();
        $this->assertNull(collect($response->json())->firstWhere('key', 'asset'));

        // enable fixed_assets → asset group present
        \App\Services\FeatureManagement::enable($this->company->id, 'fixed_assets');
        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.global', ['q' => 'Van']));
        $response->assertOk();
        $assetGroup = collect($response->json())->firstWhere('key', 'asset');
        $this->assertNotNull($assetGroup);
        $this->assertEquals('Delivery Van', $assetGroup['results'][0]['title']);
    }

    public function test_global_search_respects_user_permissions(): void
    {
        // bookkeeper passes the expanded gate but lacks users.view / branches.view / fiscal-years.view
        $bookkeeper = $this->makeUser('bookkeeper');

        Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'W-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($bookkeeper)
            ->getJson(route('accounting.search.global', ['q' => '']));

        $response->assertOk();
        $keys = array_column($response->json(), 'key');
        $this->assertContains('product', $keys);
        $this->assertNotContains('user', $keys);
        $this->assertNotContains('branch', $keys);
        $this->assertNotContains('fiscal-year', $keys);
    }

    public function test_global_search_can_filter_to_single_entity(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'W-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.global', ['q' => 'widget', 'entity' => 'product']));

        $response->assertOk();
        $groups = $response->json();
        $this->assertCount(1, $groups);
        $this->assertEquals('product', $groups[0]['key']);
    }

    public function test_global_search_is_reachable_by_bookkeeper(): void
    {
        $bookkeeper = $this->makeUser('bookkeeper');
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'W-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        // expanded gate allows bookkeeper on global search
        $global = $this->actingAs($bookkeeper)
            ->getJson(route('accounting.search.global', ['q' => 'widget']));
        $global->assertOk();

        // ...but the scoped endpoint under the accounting role gate is not
        $scoped = $this->actingAs($bookkeeper)
            ->getJson(route('accounting.search.entity', ['entity' => 'product', 'q' => 'widget']));
        $scoped->assertForbidden();
    }

    public function test_global_search_requires_authentication(): void
    {
        $response = $this->getJson(route('accounting.search.global', ['q' => 'x']));

        $response->assertUnauthorized();
    }

    // ──────────────────────────────────────────────
    // Flat "any" search (record-link wildcard pickers)
    // ──────────────────────────────────────────────

    public function test_any_search_returns_flat_rows_with_entity_key(): void
    {
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'W-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.any', ['q' => 'widget']));

        $response->assertOk();
        $rows = $response->json();

        // Contract for todoResolveLinkFromEvent(): every row must carry the
        // catalog entity key so the client can resolve the linkable class.
        $this->assertNotEmpty($rows);
        $this->assertEquals('product', $rows[0]['entity']);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('label', $rows[0]);
        $this->assertArrayHasKey('url', $rows[0]);
    }

    public function test_any_search_excludes_entities_user_cannot_view(): void
    {
        $bookkeeper = $this->makeUser('bookkeeper');
        $this->createProduct([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'W-1',
            'type' => 'inventory',
            'is_active' => true,
        ]);

        $response = $this->actingAs($bookkeeper)
            ->getJson(route('accounting.search.any', ['q' => '']));

        $response->assertOk();
        $entities = array_unique(array_column($response->json(), 'entity'));
        $this->assertContains('product', $entities);
        $this->assertNotContains('user', $entities);
    }
}
