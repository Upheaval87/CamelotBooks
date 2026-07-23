<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\BudgetVarianceService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Budget Test Co',
            'company_code' => 'BTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        $this->seedChartOfAccounts($this->company);
    }

    public function test_create_budget_with_lines(): void
    {
        $fy = $this->createFiscalYear();

        $accounts = Account::where('company_id', $this->company->id)->get();
        $salesRevenue = $accounts->firstWhere('code', '4000');
        $salaryExpense = $accounts->firstWhere('code', '6000');

        $budget = Budget::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $fy->id,
            'name' => 'Test Budget 2026',
            'description' => 'A test budget',
            'status' => Budget::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        BudgetLine::create([
            'budget_id' => $budget->id,
            'account_id' => $salesRevenue->id,
            'period_label' => 'January 2026',
            'amount' => 50000,
        ]);

        BudgetLine::create([
            'budget_id' => $budget->id,
            'account_id' => $salaryExpense->id,
            'period_label' => 'January 2026',
            'amount' => 15000,
        ]);

        $budget->load('lines');

        $this->assertEquals(2, $budget->lines->count());
        $this->assertEquals(50000, (float) $budget->lines->where('account_id', $salesRevenue->id)->first()->amount);
        $this->assertEquals(15000, (float) $budget->lines->where('account_id', $salaryExpense->id)->first()->amount);
    }

    public function test_approve_budget(): void
    {
        $fy = $this->createFiscalYear();

        $budget = Budget::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $fy->id,
            'name' => 'Draft Budget',
            'status' => Budget::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(Budget::STATUS_DRAFT, $budget->status);

        $budget->update([
            'status' => Budget::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $budget->refresh();
        $this->assertEquals(Budget::STATUS_APPROVED, $budget->status);
        $this->assertEquals($this->user->id, $budget->approved_by);
        $this->assertNotNull($budget->approved_at);
    }

    public function test_budget_variance_matches_income_statement_actuals(): void
    {
        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');
        $accounts = Account::where('company_id', $this->company->id)->get();
        $cash = $accounts->firstWhere('code', '1000');
        $salesRevenue = $accounts->firstWhere('code', '4000');
        $salaryExpense = $accounts->firstWhere('code', '6000');

        $engine = app(JournalPostingEngine::class);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-15',
            'memo' => 'January revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 60000, 'credit' => 0],
                ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 60000],
            ],
        ]);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-02-10',
            'memo' => 'February revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 45000, 'credit' => 0],
                ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 45000],
            ],
        ]);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-20',
            'memo' => 'January salaries',
            'lines' => [
                ['account_id' => $salaryExpense->id, 'debit' => 12000, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 12000],
            ],
        ]);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-02-20',
            'memo' => 'February salaries',
            'lines' => [
                ['account_id' => $salaryExpense->id, 'debit' => 14000, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 14000],
            ],
        ]);

        $budget = Budget::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $fy->id,
            'name' => 'Annual Budget 2026',
            'status' => Budget::STATUS_APPROVED,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        BudgetLine::create(['budget_id' => $budget->id, 'account_id' => $salesRevenue->id, 'period_label' => 'January 2026', 'amount' => 55000]);
        BudgetLine::create(['budget_id' => $budget->id, 'account_id' => $salesRevenue->id, 'period_label' => 'February 2026', 'amount' => 50000]);
        BudgetLine::create(['budget_id' => $budget->id, 'account_id' => $salaryExpense->id, 'period_label' => 'January 2026', 'amount' => 13000]);
        BudgetLine::create(['budget_id' => $budget->id, 'account_id' => $salaryExpense->id, 'period_label' => 'February 2026', 'amount' => 15000]);

        $service = app(BudgetVarianceService::class);
        $report = $service->generateVarianceReport($budget);

        $revenueLine = collect($report['lines'])->firstWhere('account.id', $salesRevenue->id);
        $this->assertNotNull($revenueLine);
        $this->assertEquals(105000, $revenueLine['budget']);
        $this->assertEquals(105000, $revenueLine['actual']);
        $this->assertEqualsWithDelta(0, $revenueLine['variance'], 0.01);

        $expenseLine = collect($report['lines'])->firstWhere('account.id', $salaryExpense->id);
        $this->assertNotNull($expenseLine);
        $this->assertEquals(28000, $expenseLine['budget']);
        $this->assertEquals(26000, $expenseLine['actual']);
        $this->assertEqualsWithDelta(2000, $expenseLine['variance'], 0.01);

        $this->assertEqualsWithDelta(133000, $report['total_budget'], 0.01);
        $this->assertEqualsWithDelta(131000, $report['total_actual'], 0.01);
        $this->assertEqualsWithDelta(2000, $report['total_variance'], 0.01);
    }

    public function test_company_isolation(): void
    {
        $fy = $this->createFiscalYear();

        $accounts = Account::where('company_id', $this->company->id)->get();
        $salesRevenue = $accounts->firstWhere('code', '4000');

        $budget = Budget::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $fy->id,
            'name' => 'Company A Budget',
            'status' => Budget::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $otherUser = User::factory()->create();
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        $otherUser->companies()->attach($otherCompany->id, ['role' => 'company_admin']);

        $this->seedChartOfAccounts($otherCompany);

        $otherAccounts = Account::where('company_id', $otherCompany->id)->get();
        $otherRevenue = $otherAccounts->firstWhere('code', '4000');

        $otherFy = FiscalYear::create([
            'company_id' => $otherCompany->id,
            'label' => 'FY2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        Budget::create([
            'company_id' => $otherCompany->id,
            'fiscal_year_id' => $otherFy->id,
            'name' => 'Company B Budget',
            'status' => Budget::STATUS_DRAFT,
            'created_by' => $otherUser->id,
        ]);

        session(['current_company_id' => $this->company->id]);
        $companyABudgets = Budget::where('company_id', session('current_company_id'))->get();
        $this->assertCount(1, $companyABudgets);
        $this->assertEquals('Company A Budget', $companyABudgets->first()->name);

        session(['current_company_id' => $otherCompany->id]);
        $companyBBudgets = Budget::where('company_id', session('current_company_id'))->get();
        $this->assertCount(1, $companyBBudgets);
        $this->assertEquals('Company B Budget', $companyBBudgets->first()->name);
    }

    private function createFiscalYear(): FiscalYear
    {
        return FiscalYear::create([
            'company_id' => $this->company->id,
            'label' => 'FY2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
    }

    private function seedChartOfAccounts(Company $company): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'sub_type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'operating_revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold'],
            ['code' => '6000', 'name' => 'Salary Expense', 'type' => 'expense', 'sub_type' => 'operating_expense'],
        ];

        foreach ($accounts as $a) {
            Account::create(array_merge($a, [
                'company_id' => $company->id,
                'opening_balance' => 0,
                'currency' => 'USD',
                'is_active' => true,
            ]));
        }
    }
}
