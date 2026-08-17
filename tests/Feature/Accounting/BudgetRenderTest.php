<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetAdjustment;
use App\Models\BudgetAlert;
use App\Models\BudgetAlertRule;
use App\Models\BudgetAuditLog;
use App\Models\BudgetLine;
use App\Models\BudgetTemplate;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRenderTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Budget $draft;
    protected Budget $approved;
    protected FiscalYear $fy;
    protected Account $incomeAccount;
    protected Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'BUDGETCO',
            'name' => 'Budget Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'budgets');

        $this->fy = FiscalYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026',
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'COGS',
            'type' => 'expense',
            'sub_type' => 'cost_of_goods_sold',
            'is_active' => true,
        ]);

        $this->draft = Budget::create([
            'company_id' => $this->company->id,
            'name' => 'Draft Budget',
            'code' => 'BUD-0001',
            'type' => 'operating',
            'fiscal_year_id' => $this->fy->id,
            'period' => 'annual',
            'currency' => 'USD',
            'status' => 'draft',
            'total_income' => 100000,
            'total_expenses' => 80000,
            'prepared_by' => $this->user->id,
        ]);

        BudgetLine::create([
            'company_id' => $this->company->id,
            'budget_id' => $this->draft->id,
            'line_type' => 'income',
            'account_id' => $this->incomeAccount->id,
            'annual_amount' => 100000,
            'distribution' => 'even',
        ]);

        BudgetLine::create([
            'company_id' => $this->company->id,
            'budget_id' => $this->draft->id,
            'line_type' => 'expense',
            'account_id' => $this->expenseAccount->id,
            'annual_amount' => 80000,
            'distribution' => 'even',
        ]);

        $this->approved = Budget::create([
            'company_id' => $this->company->id,
            'name' => 'Approved Budget',
            'code' => 'BUD-0002',
            'type' => 'capital',
            'fiscal_year_id' => $this->fy->id,
            'period' => 'annual',
            'currency' => 'USD',
            'status' => 'approved',
            'total_income' => 50000,
            'total_expenses' => 30000,
            'prepared_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        BudgetLine::create([
            'company_id' => $this->company->id,
            'budget_id' => $this->approved->id,
            'line_type' => 'income',
            'account_id' => $this->incomeAccount->id,
            'annual_amount' => 50000,
            'distribution' => 'even',
        ]);
    }

    private function getBudget(string $route, array $params = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)->get(route($route, $params));
    }

    public function test_dashboard_renders(): void
    {
        $this->getBudget('accounting.budgets.dashboard')->assertOk();
    }

    public function test_index_renders(): void
    {
        $this->getBudget('accounting.budgets.index')->assertOk()
            ->assertSee('Draft Budget')
            ->assertSee('Approved Budget');
    }

    public function test_create_renders(): void
    {
        $this->getBudget('accounting.budgets.create')->assertOk()
            ->assertSee('Create Budget');
    }

    public function test_show_renders(): void
    {
        $this->getBudget('accounting.budgets.show', [$this->draft])->assertOk()
            ->assertSee('Draft Budget')
            ->assertSee('BUD-0001');
    }

    public function test_edit_renders(): void
    {
        $this->getBudget('accounting.budgets.edit', [$this->draft])->assertOk()
            ->assertSee('Edit Budget')
            ->assertSee('Draft Budget');
    }

    public function test_vsactual_renders(): void
    {
        $this->getBudget('accounting.budgets.vsactual')->assertOk();
    }

    public function test_forecast_renders(): void
    {
        $this->getBudget('accounting.budgets.forecast')->assertOk();
    }

    public function test_adjustments_renders(): void
    {
        $this->getBudget('accounting.budgets.adjustments')->assertOk();
    }

    public function test_alerts_renders(): void
    {
        $this->getBudget('accounting.budgets.alerts')->assertOk();
    }

    public function test_settings_renders(): void
    {
        $this->getBudget('accounting.budgets.settings')->assertOk()
            ->assertSee('Budget Settings');
    }

    public function test_templates_renders(): void
    {
        $this->getBudget('accounting.budgets.templates')->assertOk()
            ->assertSee('Budget Templates');
    }

    public function test_reports_renders(): void
    {
        $this->getBudget('accounting.budgets.reports')->assertOk()
            ->assertSee('Budget Reports');
    }

    public function test_store_redirects(): void
    {
        $this->actingAs($this->user)->post(route('accounting.budgets.store'), [
            'name' => 'New Budget',
            'type' => 'operating',
            'fiscal_year_id' => $this->fy->id,
            'period' => 'annual',
            'currency' => 'USD',
            'action' => 'save_draft',
            'lines' => [
                ['line_type' => 'income', 'account_id' => $this->incomeAccount->id, 'annual_amount' => 50000, 'distribution' => 'even'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('budgets', ['name' => 'New Budget', 'status' => 'draft']);
    }

    public function test_submit_approve_workflow(): void
    {
        $this->actingAs($this->user)->post(route('accounting.budgets.submit', [$this->draft]))->assertRedirect();
        $this->draft->refresh();
        $this->assertEquals('pending_approval', $this->draft->status);

        $this->actingAs($this->user)->post(route('accounting.budgets.approve', [$this->draft]))->assertRedirect();
        $this->draft->refresh();
        $this->assertEquals('approved', $this->draft->status);
    }

    public function test_lock_unlock(): void
    {
        $this->actingAs($this->user)->post(route('accounting.budgets.lock', [$this->approved]))->assertRedirect();
        $this->approved->refresh();
        $this->assertEquals('locked', $this->approved->status);

        $this->actingAs($this->user)->post(route('accounting.budgets.unlock', [$this->approved]))->assertRedirect();
        $this->approved->refresh();
        $this->assertEquals('approved', $this->approved->status);
    }
}
