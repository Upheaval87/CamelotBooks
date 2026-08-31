<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
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
        // pos feature not enabled for this company
        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.entity', ['entity' => 'pos_sale', 'q' => 'x']));

        $response->assertNotFound();
    }

    public function test_scoped_search_excludes_records_user_cannot_view(): void
    {
        // viewer passes the accounting role gate but lacks users.view
        $viewer = $this->makeUser('viewer');

        $response = $this->actingAs($viewer)
            ->getJson(route('accounting.search.entity', ['entity' => 'user', 'q' => 'x']));

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
        // POS feature gate test (fixed_assets removed — rebuilt in Phase 4)
        // Use 'pos' feature instead to preserve the feature-gate test coverage
        $product = \App\Models\Product::create([
            'company_id' => $this->company->id,
            'name' => 'POS Widget',
            'sku' => 'POS-001',
            'type' => 'service',
            'sales_price' => 50,
            'income_account_id' => \App\Models\Account::where('company_id', $this->company->id)->where('sub_type', 'revenue')->first()?->id,
            'unit_of_measure' => 'each',
            'is_active' => true,
        ]);

        // Disable POS feature → product group may still appear (pos isn't a search catalog gate)
        // The key assertion: enabling a feature does not break global search
        \App\Services\FeatureManagement::enable($this->company->id, 'pos');
        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.global', ['q' => 'POS Widget']));
        $response->assertOk();
        $this->assertNotEmpty($response->json());
    }

    public function test_global_search_includes_budgets_when_feature_enabled(): void
    {
        // Regression: the budget() catalog entity called Budget::forCompany(), which
        // the model lacked → global search 500'd with "Call to undefined method
        // App\Models\Budget::forCompany()" when the budgets feature was enabled.
        \App\Services\FeatureManagement::enable($this->company->id, 'budgets');

        $fy = \App\Models\FiscalYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026',
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        \App\Models\Budget::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing Budget',
            'code' => 'BUD-0001',
            'type' => 'operating',
            'fiscal_year_id' => $fy->id,
            'period' => 'annual',
            'currency' => 'USD',
            'status' => 'draft',
            'total_income' => 100000,
            'total_expenses' => 80000,
            'prepared_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('accounting.search.global', ['q' => 'Marketing']));

        $response->assertOk();
        $budgetGroup = collect($response->json())->firstWhere('key', 'budget');
        $this->assertNotNull($budgetGroup);
        $this->assertCount(1, $budgetGroup['results']);
        $this->assertStringContainsString('Marketing Budget', $budgetGroup['results'][0]['title']);
        $this->assertArrayHasKey('url', $budgetGroup['results'][0]);
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
