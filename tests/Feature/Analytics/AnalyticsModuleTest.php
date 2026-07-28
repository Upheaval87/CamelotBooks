<?php

namespace Tests\Feature\Analytics;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\FeatureManagement;
use App\Services\Reporting\Analytics\FinancialRatiosService;
use App\Services\Reporting\Analytics\RevenueExpenseTrendService;
use App\Services\Reporting\Analytics\SalesAnalyticsService;
use App\Services\Reporting\Analytics\PurchasingAnalyticsService;
use App\Services\Reporting\Analytics\InventoryAnalyticsService;
use App\Services\Reporting\Analytics\ProfitabilityAnalyticsService;
use App\Services\Reporting\Analytics\BudgetVsActualTrendService;
use App\Services\Reporting\Analytics\CashFlowProjectionService;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsModuleTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Analytics Test Co',
            'company_code' => 'ATC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        // Enable analytics feature by default
        FeatureManagement::enable($this->company->id, 'analytics');
    }

    private function requiresMySQL(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL');
        }
    }

    // =============================================
    // FEATURE MANAGEMENT
    // =============================================

    public function test_feature_management_enable_disable(): void
    {
        $cid = $this->company->id;

        FeatureManagement::disable($cid, 'analytics');
        $this->assertFalse(FeatureManagement::isEnabled($cid, 'analytics'));

        FeatureManagement::enable($cid, 'analytics');
        $this->assertTrue(FeatureManagement::isEnabled($cid, 'analytics'));
    }

    public function test_feature_management_returns_enabled_features(): void
    {
        $cid = $this->company->id;
        FeatureManagement::enable($cid, 'inventory');
        FeatureManagement::enable($cid, 'analytics');

        $features = FeatureManagement::getEnabledFeatures($cid);
        $this->assertArrayHasKey('analytics', $features);
        $this->assertArrayHasKey('inventory', $features);
        $this->assertArrayNotHasKey('payroll', $features);
    }

    public function test_feature_management_isolated_between_companies(): void
    {
        $company2 = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        FeatureManagement::enable($this->company->id, 'analytics');
        FeatureManagement::disable($company2->id, 'analytics');

        $this->assertTrue(FeatureManagement::isEnabled($this->company->id, 'analytics'));
        $this->assertFalse(FeatureManagement::isEnabled($company2->id, 'analytics'));
    }

    public function test_feature_management_returns_false_for_unknown_feature(): void
    {
        $this->assertFalse(FeatureManagement::isEnabled($this->company->id, 'nonexistent_feature'));
    }

    // =============================================
    // REPORT REGISTRY
    // =============================================

    public function test_report_registry_returns_analytics_reports(): void
    {
        $reports = ReportRegistry::getAnalyticsReports();
        $this->assertCount(8, $reports);
        $this->assertArrayHasKey('financial_ratios', $reports);
        $this->assertArrayHasKey('cash_flow_trend', $reports);
    }

    public function test_report_registry_access_check(): void
    {
        $this->assertTrue(
            ReportRegistry::isAccessible('financial_ratios', $this->user, $this->company->id)
        );
    }

    public function test_report_registry_access_blocked_when_feature_disabled(): void
    {
        FeatureManagement::disable($this->company->id, 'analytics');

        $this->assertFalse(
            ReportRegistry::isAccessible('financial_ratios', $this->user, $this->company->id)
        );
    }

    // =============================================
    // ANALYTICS VIEW ROUTES (empty data)
    // =============================================

    public function test_financial_ratios_view_loads(): void
    {
        $this->get(route('analytics.financial-ratios'))->assertOk();
    }

    public function test_revenue_expense_trends_view_loads(): void
    {
        $this->get(route('analytics.revenue-expense-trends'))->assertOk();
    }

    public function test_sales_analytics_view_loads(): void
    {
        $this->requiresMySQL();
        $this->get(route('analytics.sales'))->assertOk();
    }

    public function test_purchasing_analytics_view_loads(): void
    {
        $this->requiresMySQL();
        FeatureManagement::enable($this->company->id, 'purchasing');
        $this->get(route('analytics.purchasing'))->assertOk();
    }

    public function test_inventory_analytics_view_loads(): void
    {
        $this->requiresMySQL();
        FeatureManagement::enable($this->company->id, 'inventory');
        $this->get(route('analytics.inventory'))->assertOk();
    }

    public function test_profitability_analytics_view_loads(): void
    {
        $this->get(route('analytics.profitability'))->assertOk();
    }

    public function test_budget_vs_actual_trend_view_loads(): void
    {
        FeatureManagement::enable($this->company->id, 'budgets');
        $this->get(route('analytics.budget-vs-actual-trend'))->assertOk();
    }

    public function test_cash_flow_trend_view_loads(): void
    {
        $this->get(route('analytics.cash-flow-trend'))->assertOk();
    }

    // =============================================
    // FINANCIAL RATIOS SERVICE (empty data)
    // =============================================

    public function test_financial_ratios_empty_data_returns_n_a(): void
    {
        $service = new FinancialRatiosService();
        $data = $service->calculate($this->company->id, now()->format('Y-m-d'));

        $this->assertArrayHasKey('ratios', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertEquals(0, $data['summary']['total_assets']);
        $this->assertEquals(0, $data['summary']['total_liabilities']);
    }

    // =============================================
    // FINANCIAL RATIOS WITH ACTUAL DATA
    // =============================================

    public function test_financial_ratios_with_data_computes_values(): void
    {
        // Create accounts
        $cash = Account::create([
            'company_id' => $this->company->id, 'code' => '1000', 'name' => 'Cash',
            'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true,
        ]);
        $ar = Account::create([
            'company_id' => $this->company->id, 'code' => '1100', 'name' => 'AR',
            'type' => 'asset', 'sub_type' => 'current_asset', 'is_active' => true,
        ]);
        $ap = Account::create([
            'company_id' => $this->company->id, 'code' => '2000', 'name' => 'AP',
            'type' => 'liability', 'sub_type' => 'current_liability', 'is_active' => true,
        ]);
        $revenue = Account::create([
            'company_id' => $this->company->id, 'code' => '4000', 'name' => 'Revenue',
            'type' => 'income', 'sub_type' => 'revenue', 'is_active' => true,
        ]);
        $cogs = Account::create([
            'company_id' => $this->company->id, 'code' => '5000', 'name' => 'COGS',
            'type' => 'expense', 'sub_type' => 'cost_of_goods_sold', 'is_active' => true,
        ]);

        // Post a journal entry: DR Cash 1000, CR Revenue 1000
        $je1 = \App\Models\JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-001',
            'created_by' => $this->user->id,
            'date' => now()->subMonth()->format('Y-m-d'),
            'status' => 'posted',
            'reference' => 'TEST-JE-001',
            'memo' => 'Test revenue',
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je1->id, 'account_id' => $cash->id,
            'debit' => 1000, 'credit' => 0,
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je1->id, 'account_id' => $revenue->id,
            'debit' => 0, 'credit' => 1000,
        ]);

        // Post a journal entry: DR COGS 600, CR AP 600
        $je2 = \App\Models\JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-002',
            'created_by' => $this->user->id,
            'date' => now()->subMonth()->format('Y-m-d'),
            'status' => 'posted',
            'reference' => 'TEST-JE-002',
            'memo' => 'Test cogs',
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je2->id, 'account_id' => $cogs->id,
            'debit' => 600, 'credit' => 0,
        ]);
        \App\Models\JournalEntryLine::create([
            'journal_entry_id' => $je2->id, 'account_id' => $ap->id,
            'debit' => 0, 'credit' => 600,
        ]);

        $service = new FinancialRatiosService();
        $data = $service->calculate($this->company->id, now()->format('Y-m-d'));

        // Revenue should be 1000
        $this->assertEquals(1000, $data['summary']['total_revenue']);
        // Net income should be 400 (1000 - 600)
        $this->assertEquals(400, $data['summary']['net_income']);

        // Current ratio should be computed (current assets / current liabilities)
        $currentRatio = $data['ratios']['liquidity']['current_ratio'];
        $this->assertNotNull($currentRatio);
        $this->assertIsArray($currentRatio);
        $this->assertArrayHasKey('value', $currentRatio);

        // Gross margin should be > 0
        $grossMargin = $data['ratios']['profitability']['gross_margin'];
        $this->assertNotNull($grossMargin);
        $this->assertGreaterThan(0, $grossMargin['value']);
    }

    // =============================================
    // REVENUE EXPENSE TRENDS SERVICE (empty data)
    // =============================================

    public function test_revenue_expense_trends_empty_data(): void
    {
        $service = new RevenueExpenseTrendService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            3
        );

        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(3, $data['labels']);
        $this->assertEquals(0, $data['total_revenue']);
        $this->assertEquals(0, $data['total_expense']);
    }

    // =============================================
    // SALES ANALYTICS SERVICE (empty data)
    // =============================================

    public function test_sales_analytics_empty_data(): void
    {
        $service = new SalesAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('revenue', $data);
        $this->assertArrayHasKey('monthly_summary', $data);
        $this->assertArrayHasKey('conversion', $data);
        $this->assertEquals(0, $data['revenue']['total_income']);
    }

    // =============================================
    // INVENTORY ANALYTICS SERVICE (empty data)
    // =============================================

    public function test_inventory_analytics_empty_data(): void
    {
        $service = new InventoryAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->format('Y-m-d'),
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('current_value', $data);
        $this->assertEquals(0, $data['current_value']['total_value']);
        $this->assertEquals(0, $data['current_value']['item_count']);
    }

    // =============================================
    // PROFITABILITY ANALYTICS SERVICE (empty data)
    // =============================================

    public function test_profitability_analytics_empty_data(): void
    {
        $service = new ProfitabilityAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('by_branch', $data);
        $this->assertArrayHasKey('by_cost_center', $data);
        $this->assertArrayHasKey('by_product', $data);
        $this->assertEmpty($data['by_branch']);
    }

    // =============================================
    // CASH FLOW PROJECTION SERVICE (empty data)
    // =============================================

    public function test_cash_flow_projection_empty_data(): void
    {
        $service = new CashFlowProjectionService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            null,
            3
        );

        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('projection_net', $data);
        $this->assertCount(3, $data['projection_net']);
        $this->assertTrue($data['is_projection']);
    }

    // =============================================
    // PURCHASING ANALYTICS SERVICE (empty data)
    // =============================================

    public function test_purchasing_analytics_empty_data(): void
    {
        $service = new PurchasingAnalyticsService();
        $data = $service->calculate(
            $this->company->id,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $this->assertArrayHasKey('monthly_summary', $data);
        $this->assertArrayHasKey('top_vendors', $data);
        $this->assertArrayHasKey('ppv_trend', $data);
        $this->assertEmpty($data['monthly_summary']);
    }

    // =============================================
    // BUDGET VS ACTUAL TREND SERVICE
    // =============================================

    public function test_budget_vs_actual_trend_no_fiscal_year(): void
    {
        $service = new BudgetVsActualTrendService();
        $data = $service->calculate($this->company->id);

        $this->assertArrayHasKey('error', $data);
    }

    // =============================================
    // NAVIGATION INTEGRATION
    // =============================================

    public function test_analytics_navigation_visible_to_admin(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Analytics');
    }

    // =============================================
    // FEATURE GATING
    // =============================================

    public function test_analytics_route_accessible_when_feature_enabled(): void
    {
        FeatureManagement::enable($this->company->id, 'analytics');
        $this->get(route('analytics.financial-ratios'))->assertOk();
    }

    public function test_inventory_analytics_route_loads_when_feature_enabled(): void
    {
        $this->requiresMySQL();
        FeatureManagement::enable($this->company->id, 'inventory');
        $this->get(route('analytics.inventory'))->assertOk();
    }

    public function test_purchasing_analytics_route_loads_when_feature_enabled(): void
    {
        $this->requiresMySQL();
        FeatureManagement::enable($this->company->id, 'purchasing');
        $this->get(route('analytics.purchasing'))->assertOk();
    }
}
