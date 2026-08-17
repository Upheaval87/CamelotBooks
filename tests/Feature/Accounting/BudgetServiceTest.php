<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetAdjustment;
use App\Models\BudgetAuditLog;
use App\Models\BudgetLine;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\User;
use App\Services\Accounting\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private FiscalYear $fy;
    private Account $incomeAccount;
    private Account $expenseAccount;
    private BudgetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Budget Service Test Co',
            'company_code' => 'BST',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

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
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'Operating Expenses',
            'type' => 'expense',
            'sub_type' => 'operating',
            'is_active' => true,
        ]);

        $this->service = app(BudgetService::class);
    }

    public function test_create_budget_with_lines(): void
    {
        $budget = $this->service->create([
            'company_id'     => $this->company->id,
            'name'           => 'Test Budget',
            'type'           => 'operating',
            'fiscal_year_id' => $this->fy->id,
            'period'         => 'annual',
            'currency'       => 'MWK',
            'lines'          => [
                ['line_type' => 'income', 'account_id' => $this->incomeAccount->id, 'annual_amount' => 200000],
                ['line_type' => 'expense', 'account_id' => $this->expenseAccount->id, 'annual_amount' => 150000],
            ],
        ], $this->user->id);

        $this->assertDatabaseHas('budgets', ['code' => 'BUD-0001', 'name' => 'Test Budget']);
        $this->assertEquals(200000, $budget->total_income);
        $this->assertEquals(150000, $budget->total_expenses);
        $this->assertEquals('draft', $budget->status);
        $this->assertCount(2, $budget->lines);
        $this->assertDatabaseHas('budget_audit_logs', ['action' => 'created', 'budget_id' => $budget->id]);
    }

    public function test_update_budget_draft(): void
    {
        $budget = $this->createDraftBudget();

        $updated = $this->service->update($budget, [
            'name' => 'Updated Budget Name',
            'lines' => [
                ['line_type' => 'expense', 'account_id' => $this->expenseAccount->id, 'annual_amount' => 200000],
            ],
        ], $this->user->id);

        $this->assertEquals('Updated Budget Name', $updated->name);
        $this->assertEquals(200000, $updated->total_expenses);
        $this->assertDatabaseHas('budget_audit_logs', ['action' => 'updated', 'budget_id' => $budget->id]);
    }

    public function test_cannot_update_approved_budget(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'approved']);

        $this->expectException(\RuntimeException::class);
        $this->service->update($budget, ['name' => 'Hacked'], $this->user->id);
    }

    public function test_submit_for_approval(): void
    {
        $budget = $this->createDraftBudget();

        $submitted = $this->service->submitForApproval($budget, $this->user->id);

        $this->assertEquals('pending_approval', $submitted->status);
        $this->assertDatabaseHas('budget_audit_logs', ['action' => 'submitted']);
    }

    public function test_approve_budget(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'pending_approval']);

        $approved = $this->service->approve($budget, $this->user->id, 'Looks good');

        $this->assertEquals('approved', $approved->status);
        $this->assertDatabaseHas('budget_audit_logs', ['action' => 'approved']);
    }

    public function test_reject_budget(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'pending_approval']);

        $rejected = $this->service->reject($budget, $this->user->id, 'Over budget');

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals('Over budget', $rejected->rejection_reason);
        $this->assertTrue($rejected->isEditable());
    }

    public function test_lock_approved_budget(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'approved']);

        $locked = $this->service->lock($budget, $this->user->id);

        $this->assertEquals('locked', $locked->status);
        $this->assertNotNull($locked->locked_at);
        $this->assertFalse($locked->isEditable());
    }

    public function test_unlock_budget(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'locked', 'locked_by' => $this->user->id, 'locked_at' => now()]);

        $unlocked = $this->service->unlock($budget, $this->user->id);

        $this->assertEquals('approved', $unlocked->status);
        $this->assertNull($unlocked->locked_at);
    }

    public function test_create_adjustment(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'approved']);

        $adj = $this->service->createAdjustment([
            'budget_id'       => $budget->id,
            'budget_line_id'  => $budget->lines()->first()->id,
            'type'            => 'increase',
            'amount'          => 10000,
            'reason'          => 'Need more budget for Q3',
            'original_amount' => 150000,
        ], $this->user->id);

        $this->assertDatabaseHas('budget_adjustments', ['code' => 'ADJ-0001']);
        $this->assertEquals('pending', $adj->status);
        $this->assertDatabaseHas('budget_audit_logs', ['action' => 'adjustment']);
    }

    public function test_approve_adjustment_increases_line(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'approved']);

        $line = $budget->lines()->first();
        $originalAmount = $line->annual_amount;

        $adj = $this->service->createAdjustment([
            'budget_id'       => $budget->id,
            'budget_line_id'  => $line->id,
            'type'            => 'increase',
            'amount'          => 5000,
            'reason'          => 'Additional spend',
            'original_amount' => $originalAmount,
        ], $this->user->id);

        $approved = $this->service->approveAdjustment($adj, $this->user->id, 'Approved');

        $this->assertEquals('approved', $approved->status);
        $line->refresh();
        $this->assertEquals($originalAmount + 5000, $line->annual_amount);
    }

    public function test_approve_transfer_adjustment(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'approved']);

        $lines = $budget->lines()->get();
        $fromLine = $lines->first();
        $toLine = $lines->last();
        $originalFrom = $fromLine->annual_amount;
        $originalTo = $toLine->annual_amount;

        $adj = $this->service->createAdjustment([
            'budget_id'      => $budget->id,
            'type'           => 'transfer',
            'from_line_id'   => $fromLine->id,
            'to_line_id'     => $toLine->id,
            'amount'         => 20000,
            'reason'         => 'Shifting budget from income to expense',
        ], $this->user->id);

        $this->service->approveAdjustment($adj, $this->user->id);

        $fromLine->refresh();
        $toLine->refresh();
        $this->assertEquals($originalFrom - 20000, $fromLine->annual_amount);
        $this->assertEquals($originalTo + 20000, $toLine->annual_amount);
    }

    public function test_reject_adjustment(): void
    {
        $budget = $this->createDraftBudget();
        $budget->update(['status' => 'approved']);

        $adj = $this->service->createAdjustment([
            'budget_id'      => $budget->id,
            'type'           => 'reduce',
            'amount'         => 10000,
            'reason'         => 'Cost cutting',
        ], $this->user->id);

        $rejected = $this->service->rejectAdjustment($adj, $this->user->id, 'Not justified');

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals('Not justified', $rejected->approval_comment);
    }

    public function test_sequential_codes(): void
    {
        $b1 = $this->service->create([
            'company_id' => $this->company->id,
            'name' => 'First',
            'fiscal_year_id' => $this->fy->id,
        ], $this->user->id);

        $b2 = $this->service->create([
            'company_id' => $this->company->id,
            'name' => 'Second',
            'fiscal_year_id' => $this->fy->id,
        ], $this->user->id);

        $this->assertEquals('BUD-0001', $b1->code);
        $this->assertEquals('BUD-0002', $b2->code);
    }

    public function test_audit_log_created_for_every_action(): void
    {
        $budget = $this->createDraftBudget();

        $this->service->submitForApproval($budget, $this->user->id);
        $budget->refresh();

        $this->service->approve($budget, $this->user->id);
        $budget->refresh();

        $this->service->lock($budget, $this->user->id);

        $logs = BudgetAuditLog::where('budget_id', $budget->id)->get();
        // created (from createDraftBudget) + submitted + approved + locked = 4
        $this->assertCount(4, $logs);
    }

    private function createDraftBudget(): Budget
    {
        return $this->service->create([
            'company_id'     => $this->company->id,
            'name'           => 'Test Budget',
            'type'           => 'operating',
            'fiscal_year_id' => $this->fy->id,
            'period'         => 'annual',
            'currency'       => 'MWK',
            'lines'          => [
                ['line_type' => 'income', 'account_id' => $this->incomeAccount->id, 'annual_amount' => 200000],
                ['line_type' => 'expense', 'account_id' => $this->expenseAccount->id, 'annual_amount' => 150000],
            ],
        ], $this->user->id);
    }
}
