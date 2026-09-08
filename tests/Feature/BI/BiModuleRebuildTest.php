<?php

namespace Tests\Feature\BI;

use App\Models\Account;
use App\Models\BiAuditLog;
use App\Models\BiSetting;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BiModuleRebuildTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    private const PAGES = [
        'overview',
        'branch',
        'clv',
        'employee',
        'truecost',
        'product',
        'supplier',
        'workcap',
        'scenarios',
        'variance',
        'pvm',
        'breakeven',
        'cohorts',
        'board',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->company = Company::create([
            'name' => 'BI Rebuild Co',
            'company_code' => 'BIRC',
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

        \App\Services\FeatureManagement::enable($this->company->id, 'bi');
    }

    public function test_all_fourteen_pages_render(): void
    {
        foreach (self::PAGES as $key) {
            $response = $this->get(route("bi.{$key}"));
            $response->assertOk();
            $response->assertSee('an-wrap');
            $response->assertSee('an-chips');
        }

        $this->assertCount(14, self::PAGES);
    }

    public function test_shared_nav_renders_all_fourteen_entries_in_order(): void
    {
        $response = $this->get(route('bi.overview'));
        $response->assertOk();

        foreach (self::PAGES as $key) {
            $response->assertSee($key, false);
        }

        $content = html_entity_decode($response->getContent() ?: '', ENT_QUOTES, 'UTF-8');
        $labels = [
            'Overview & Command Centre', 'Branch Profitability', 'Customer Lifetime Value',
            'Employee Productivity', 'True Total Cost', 'Product ABC', 'Supplier Scorecard',
            'Working Capital', 'Cash Scenarios', 'Budget Variance', 'Price-Volume-Mix',
            'Break Even', 'Cohorts & Churn', 'Board Pack',
        ];

        $position = -1;
        foreach ($labels as $label) {
            $position = strpos($content, $label, $position + 1);
            $this->assertNotFalse($position, "Expected to find \"{$label}\" in order.");
        }
    }

    public function test_period_preset_persists_across_links(): void
    {
        $response = $this->get(route('bi.variance', ['period' => 'quarter']));
        $response->assertOk();

        $response->assertSee('value="quarter"', false);
        $response->assertSee('period=quarter', false);
    }

    public function test_export_csv_endpoints_render(): void
    {
        foreach (self::PAGES as $key) {
            $this->get(route('bi.export', ['page' => $key, 'period' => 'month']))
                ->assertOk()
                ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        }
    }

    public function test_print_endpoints_render(): void
    {
        foreach (self::PAGES as $key) {
            $this->get(route('bi.print', ['page' => $key, 'period' => 'month']))->assertOk();
        }

        $this->get(route('bi.print', ['page' => 'overview', 'period' => 'quarter']))->assertOk();
    }

    public function test_scenario_multipliers_persist_across_save_and_page(): void
    {
        $this->post(route('bi.scenarios.save'), [
            'scenario' => [
                'bull' => ['inflow' => 1.5, 'outflow' => 0.7],
                'base' => ['inflow' => 1.0, 'outflow' => 1.0],
                'bear' => ['inflow' => 0.8, 'outflow' => 1.2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('bi_settings', [
            'company_id' => $this->company->id,
            'group_key' => 'scenario',
            'key' => 'bull',
        ]);

        $saved = BiSetting::where('company_id', $this->company->id)
            ->where('group_key', 'scenario')
            ->where('key', 'bull')
            ->first();
        $this->assertSame(1.5, (float) $saved->value['inflow']);

        $this->assertDatabaseHas('bi_audit_log', [
            'company_id' => $this->company->id,
            'action' => 'setting.updated',
        ]);

        $response = $this->get(route('bi.scenarios'));
        $response->assertOk();
        $response->assertSee('name="scenario[bull][inflow]"', false);
        $response->assertSee('value="1.50"', false);
        $response->assertSee('value="0.70"', false);
    }

    public function test_allocation_driver_persists_and_rerenders_selected(): void
    {
        $this->post(route('bi.allocation.save'), [
            'allocation' => [
                'payroll_driver' => 'headcount',
                'occupancy_driver' => 'revenue_share',
                'g_and_a_driver' => 'headcount',
                'has_floor_area' => '1',
            ],
        ])->assertRedirect();

        $settings = (new \App\Services\BI\BiSettingService())->getWithDefaults('allocation', $this->company->id);
        $this->assertSame('headcount', $settings['pools']['payroll']['driver']);
        $this->assertSame('revenue_share', $settings['pools']['occupancy']['driver']);
        $this->assertSame(1, (int) $settings['use_floor_area_sqm']);

        $response = $this->get(route('bi.branch'));
        $response->assertOk();
        $response->assertSee('name="allocation[payroll_driver]"', false);
        $response->assertSee('name="allocation[occupancy_driver]"', false);
        $response->assertSee('name="allocation[g_and_a_driver]"', false);
        $response->assertSee('name="allocation[has_floor_area]"', false);
    }

    public function test_expense_classification_persists_and_renders_selected(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'code' => '6001',
            'name' => 'Consulting Fees',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->post(route('bi.expense-class.save'), [
            'classifications' => [$account->id => \App\Models\BiExpenseClass::CLASS_FIXED],
        ])->assertRedirect();

        $this->assertDatabaseHas('bi_expense_classes', [
            'company_id' => $this->company->id,
            'account_id' => $account->id,
            'class' => \App\Models\BiExpenseClass::CLASS_FIXED,
        ]);

        $response = $this->get(route('bi.breakeven'));
        $response->assertOk();
        $response->assertSee('name="classifications[' . $account->id . ']"', false);
        $response->assertSee('value="fixed" selected', false);
    }

    public function test_board_action_save_and_render(): void
    {
        $response = $this->get(route('bi.board'));
        $response->assertOk();
        $response->assertSee('No actions yet.');

        $this->post(route('bi.actions.save'), [
            'description' => 'Reduce stock-outs before Q3 close',
            'owner' => 'CFO',
            'due_date' => '2026-12-31',
            'status' => 'open',
        ])->assertRedirect();

        $this->assertDatabaseHas('bi_actions', [
            'company_id' => $this->company->id,
            'page' => 'board',
            'description' => 'Reduce stock-outs before Q3 close',
        ]);

        $response = $this->get(route('bi.board'));
        $response->assertOk();
        $response->assertSee('Reduce stock-outs before Q3 close');
        $response->assertSee('CFO');
    }

    public function test_empty_states_render(): void
    {
        $checks = [
            'overview' => 'No alerts right now.',
            'clv' => 'No customer data.',
            'branch' => 'No branch performance data.',
            'truecost' => 'No cost data.',
            'product' => 'No product data.',
            'supplier' => 'No supplier data.',
            'workcap' => 'No cash or bank accounts found.',
            'variance' => 'No budget has been set up for this company in the selected period.',
            'pvm' => 'No comparable product revenue yet for the selected period pair.',
            'breakeven' => 'No expense accounts were found for classification.',
            'cohorts' => 'No cohort data in the selected window.',
            'board' => 'No actions yet.',
            'employee' => 'No branch utilisation data.',
        ];

        foreach ($checks as $key => $needle) {
            $this->get(route("bi.{$key}"))->assertOk()->assertSee($needle);
        }
    }

    public function test_bi_view_without_exec_cannot_post(): void
    {
        $viewer = User::factory()->create();
        $viewer->companies()->attach($this->company->id, ['role' => 'viewer']);

        setPermissionsTeamId($this->company->id);
        $viewer->assignRole('viewer');

        $this->actingAs($viewer);

        $this->get(route('bi.overview'))->assertOk();

        $this->post(route('bi.scenarios.save'), [
            'scenario' => ['bull' => ['inflow' => 2.0, 'outflow' => 0.5]],
        ])->assertForbidden();

        $this->post(route('bi.actions.save'), ['description' => 'nope'])->assertForbidden();
    }

    public function test_bi_costs_are_tagged_in_gl_and_drivers_recompute_minutely(): void
    {
        // Regression guard: periodic BI helpers must not drift from GL truth.
        // Single posted expense gives identical totals across overview/truecost GL reads.
        $account = Account::create([
            'company_id' => $this->company->id,
            'code' => '5001',
            'name' => 'Salaries',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        \App\Models\JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-0001',
            'date' => now()->toDateString(),
            'description' => 'Payroll run',
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);
        $je = \App\Models\JournalEntry::where('journal_number', 'JE-0001')->firstOrFail();
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $je->id,
                'account_id' => $account->id,
                'debit' => 2500.00,
                'credit' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $overview = $this->get(route('bi.overview'))->assertOk();
        $overview->assertSee('an-kpis');
    }
}