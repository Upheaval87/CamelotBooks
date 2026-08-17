<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Budget;
use App\Models\BudgetAdjustment;
use App\Models\BudgetAlert;
use App\Models\BudgetAlertRule;
use App\Models\BudgetAuditLog;
use App\Models\BudgetLine;
use App\Models\BudgetTemplate;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BudgetSchemaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create([
            'name' => 'Budget Test Co',
            'company_code' => 'BTC',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->actingAs($this->user);
        session(['current_company_id' => $this->company->id]);
    }

    private function seedDefaults(): array
    {
        $fy = \App\Models\FiscalYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026',
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        $account = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        $expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'Operating Expenses',
            'type' => 'expense',
            'sub_type' => 'operating',
            'is_active' => true,
        ]);

        return [$fy, $account, $expenseAccount];
    }

    public function test_budget_crud(): void
    {
        [$fy, $account, $expenseAccount] = $this->seedDefaults();

        $budget = Budget::create([
            'company_id'     => $this->company->id,
            'name'           => 'FY 2026 Operating Budget',
            'code'           => 'BUD-0001',
            'type'           => 'operating',
            'fiscal_year_id' => $fy->id,
            'period'         => 'annual',
            'status'         => 'draft',
            'currency'       => 'MWK',
            'total_income'   => 120000,
            'total_expenses' => 100000,
            'prepared_by'    => $this->user->id,
        ]);

        $this->assertDatabaseHas('budgets', ['code' => 'BUD-0001', 'name' => 'FY 2026 Operating Budget']);
        $this->assertEquals('operating', $budget->type);
        $this->assertTrue($budget->isEditable());
        $this->assertTrue($budget->isSubmittable());
        $this->assertFalse($budget->isLockable());
        $this->assertEquals('Draft', $budget->statusLabel());
        $this->assertEquals('Operating Budget', $budget->typeLabel());
    }

    public function test_budget_lines(): void
    {
        [$fy, $account, $expenseAccount] = $this->seedDefaults();

        $budget = Budget::create([
            'company_id'     => $this->company->id,
            'name'           => 'Test Budget',
            'code'           => 'BUD-0002',
            'type'           => 'operating',
            'fiscal_year_id' => $fy->id,
            'status'         => 'draft',
            'currency'       => 'MWK',
            'total_income'   => 120000,
            'total_expenses' => 100000,
            'prepared_by'    => $this->user->id,
        ]);

        $line = BudgetLine::create([
            'company_id'     => $this->company->id,
            'budget_id'      => $budget->id,
            'line_type'      => 'income',
            'account_id'     => $account->id,
            'annual_amount'  => 120000,
            'monthly_amount' => 10000,
            'distribution'   => 'even',
        ]);

        $this->assertDatabaseHas('budget_lines', ['budget_id' => $budget->id]);
        $this->assertEquals(120000, $line->annual_amount);
        $this->assertCount(12, $line->monthlyBreakdown());
        $this->assertEqualsWithDelta(10000, $line->monthlyBreakdown()[0], 0.01);
    }

    public function test_budget_templates(): void
    {
        $template = BudgetTemplate::create([
            'company_id'    => $this->company->id,
            'name'          => 'Standard Operating',
            'basis'         => 'standard',
            'lines_count'   => 5,
            'template_data' => ['lines' => []],
            'created_by'    => $this->user->id,
        ]);

        $this->assertDatabaseHas('budget_templates', ['name' => 'Standard Operating']);
        $this->assertEquals('Standard Budget', $template->basisLabel());
    }

    public function test_budget_adjustments(): void
    {
        [$fy, $account, $expenseAccount] = $this->seedDefaults();

        $budget = Budget::create([
            'company_id'     => $this->company->id,
            'name'           => 'Test Budget',
            'code'           => 'BUD-0003',
            'type'           => 'operating',
            'fiscal_year_id' => $fy->id,
            'status'         => 'approved',
            'currency'       => 'MWK',
            'total_income'   => 120000,
            'total_expenses' => 100000,
            'prepared_by'    => $this->user->id,
        ]);

        $line = BudgetLine::create([
            'company_id'    => $this->company->id,
            'budget_id'     => $budget->id,
            'line_type'     => 'expense',
            'account_id'    => $expenseAccount->id,
            'annual_amount' => 50000,
            'distribution'  => 'even',
        ]);

        $adj = BudgetAdjustment::create([
            'company_id'      => $this->company->id,
            'budget_id'       => $budget->id,
            'budget_line_id'  => $line->id,
            'code'            => 'ADJ-0001',
            'type'            => 'increase',
            'amount'          => 5000,
            'reason'          => 'Additional headcount',
            'status'          => 'pending',
            'requested_by'    => $this->user->id,
            'original_amount' => 50000,
        ]);

        $this->assertDatabaseHas('budget_adjustments', ['code' => 'ADJ-0001']);
        $this->assertEquals('Increase', $adj->typeLabel());
        $this->assertEquals('Pending', $adj->statusLabel());
    }

    public function test_budget_alerts_and_rules(): void
    {
        $rule = BudgetAlertRule::create([
            'company_id'           => $this->company->id,
            'name'                 => 'Spending 85%',
            'is_active'            => true,
            'rule_type'            => 'threshold',
            'warn_threshold'       => 85,
            'exceed_threshold'     => 100,
            'unusual_multiplier'   => 1.25,
            'low_balance_threshold' => 10,
            'scope'                => 'budget',
            'channels'             => ['email', 'system'],
            'recipient_ids'        => [$this->user->id],
        ]);

        $alert = BudgetAlert::create([
            'company_id' => $this->company->id,
            'rule_id'    => $rule->id,
            'severity'   => 'nearing',
            'message'    => 'Marketing budget at 87% utilization',
            'is_read'    => false,
        ]);

        $this->assertDatabaseHas('budget_alert_rules', ['name' => 'Spending 85%']);
        $this->assertDatabaseHas('budget_alerts', ['severity' => 'nearing']);
        $this->assertEquals('Threshold Alert', $rule->ruleTypeLabel());
        $this->assertEquals('Nearing Limit', $alert->severityLabel());
    }

    public function test_budget_audit_log(): void
    {
        [$fy, $account, $expenseAccount] = $this->seedDefaults();

        $budget = Budget::create([
            'company_id'     => $this->company->id,
            'name'           => 'Test Budget',
            'code'           => 'BUD-0004',
            'type'           => 'operating',
            'fiscal_year_id' => $fy->id,
            'status'         => 'draft',
            'currency'       => 'MWK',
            'total_income'   => 120000,
            'total_expenses' => 100000,
            'prepared_by'    => $this->user->id,
        ]);

        BudgetAuditLog::create([
            'company_id'  => $this->company->id,
            'budget_id'   => $budget->id,
            'user_id'     => $this->user->id,
            'action'      => 'created',
            'after'       => ['name' => 'Test Budget', 'status' => 'draft'],
            'description' => 'Budget created',
            'created_at'  => now(),
        ]);

        $this->assertDatabaseHas('budget_audit_logs', ['action' => 'created']);
    }

    public function test_budget_status_transitions(): void
    {
        [$fy, $account, $expenseAccount] = $this->seedDefaults();

        $budget = Budget::create([
            'company_id'     => $this->company->id,
            'name'           => 'Status Test',
            'code'           => 'BUD-0005',
            'type'           => 'operating',
            'fiscal_year_id' => $fy->id,
            'status'         => 'draft',
            'currency'       => 'MWK',
            'total_income'   => 120000,
            'total_expenses' => 100000,
            'prepared_by'    => $this->user->id,
        ]);

        // draft → editable, submittable, not lockable
        $this->assertTrue($budget->isEditable());
        $this->assertTrue($budget->isSubmittable());
        $this->assertFalse($budget->isLockable());

        // approved → not editable, not submittable, lockable
        $budget->update(['status' => 'approved']);
        $this->assertFalse($budget->isEditable());
        $this->assertFalse($budget->isSubmittable());
        $this->assertTrue($budget->isLockable());

        // locked → not editable
        $budget->update(['status' => 'locked']);
        $this->assertFalse($budget->isEditable());
        $this->assertFalse($budget->isSubmittable());
        $this->assertFalse($budget->isLockable());

        // rejected → editable
        $budget->update(['status' => 'rejected']);
        $this->assertTrue($budget->isEditable());
        $this->assertFalse($budget->isSubmittable());
    }
}
