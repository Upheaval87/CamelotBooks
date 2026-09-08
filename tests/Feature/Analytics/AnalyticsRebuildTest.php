<?php

namespace Tests\Feature\Analytics;

use App\Models\Company;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsRebuildTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    private const PAGES = [
        'overview',
        'financial-ratios',
        'revenue-expense-trends',
        'sales',
        'profitability',
        'cash-flow-trend',
        'purchasing',
        'inventory',
        'expenses',
        'customers',
        'working-capital',
        'budget-vs-actual',
        'tax',
        'forecasts',
        'branches',
    ];

    private const MYSQL_ONLY = ['sales', 'purchasing', 'inventory', 'expenses', 'tax', 'forecasts'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->company = Company::create([
            'name' => 'Analytics Rebuild Co',
            'company_code' => 'ARC',
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'analytics');
    }

    public function test_all_fifteen_analytics_pages_render(): void
    {
        foreach (self::PAGES as $key) {
            if (in_array($key, self::MYSQL_ONLY, true) && DB::getDriverName() !== 'mysql') {
                continue;
            }

            $response = $this->get(route("analytics.{$key}"));
            $response->assertOk();
            $response->assertSee('an-wrap');
        }

        $this->assertCount(15, self::PAGES);
    }

    public function test_shared_head_renders_nav_with_all_fifteen_entries(): void
    {
        $response = $this->get(route('analytics.overview'));
        $response->assertOk();

        $response->assertSee('an-chips');

        foreach (self::PAGES as $key) {
            $response->assertSee($key, false);
        }

        $this->assertSeeInOrder([
            'Overview', 'Financial Ratios', 'Revenue & Expense', 'Sales', 'Profitability',
            'Cash Flow', 'Purchasing', 'Inventory', 'Expenses', 'Customers',
            'Working Capital', 'Budget vs Actual', 'Tax', 'Forecasts', 'Branches',
        ], $response, true);
    }

    public function test_period_preset_persists_across_links(): void
    {
        $response = $this->get(route('analytics.revenue-expense-trends', ['period' => 'quarter']));
        $response->assertOk();

        $response->assertSee('value="quarter"', false);
        $response->assertSee('period=quarter', false);
    }

    public function test_export_csv_endpoints_render(): void
    {
        foreach (self::PAGES as $key) {
            if (in_array($key, self::MYSQL_ONLY, true) && DB::getDriverName() !== 'mysql') {
                continue;
            }

            $this->get(route('analytics.export', ['page' => $key, 'period' => 'month']))
                ->assertOk()
                ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        }
    }

    public function test_print_endpoint_renders(): void
    {
        foreach (self::PAGES as $key) {
            if (in_array($key, self::MYSQL_ONLY, true) && DB::getDriverName() !== 'mysql') {
                continue;
            }

            $this->get(route('analytics.print', ['page' => $key, 'period' => 'month']))->assertOk();
        }

        $this->get(route('analytics.print', ['page' => 'overview', 'period' => 'quarter']))->assertOk();
    }

    public function test_read_only_no_mutating_routes(): void
    {
        $posts = collect(app('router')->getRoutes())->filter(
            fn ($r) => str_starts_with((string) $r->getName(), 'analytics.') && in_array('POST', $r->methods(), true)
        );

        $this->assertCount(0, $posts, 'analytics module must expose no POST routes');

        $content = $this->get(route('analytics.overview'))->assertOk()->getContent() ?: '';
        $this->assertDoesNotMatchRegularExpression('#action="[^"]*/accounting/analytics[^"]*"#', $content);
    }

    private function assertSeeInOrder(array $needles, $response, bool $escaped = false): void
    {
        $content = $response->getContent() ?: '';

        if ($escaped) {
            $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        }

        $position = -1;
        foreach ($needles as $needle) {
            $position = strpos($content, $needle, $position + 1);
            $this->assertNotFalse($position, "Expected to find \"{$needle}\" in order.");
        }
    }
}