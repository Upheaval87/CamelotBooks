<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostCenterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'CC Test Co',
            'company_code' => 'CCTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        $start = now()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => $start->format('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'open',
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    public function test_create_cost_center(): void
    {
        $cc = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'description' => 'Marketing department',
            'is_active' => true,
        ]);

        $this->assertEquals('Marketing', $cc->name);
        $this->assertEquals('MKT', $cc->code);
        $this->assertTrue($cc->is_active);
        $this->assertEquals($this->company->id, $cc->company_id);
    }

    public function test_unique_code_per_company(): void
    {
        CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing 2',
            'code' => 'MKT',
            'is_active' => true,
        ]);
    }

    public function test_cost_center_isolation_between_companies(): void
    {
        $company2 = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OTHR',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        CostCenter::create([
            'company_id' => $company2->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $this->assertEquals(1, CostCenter::where('company_id', $this->company->id)->count());
        $this->assertEquals(1, CostCenter::where('company_id', $company2->id)->count());
    }

    public function test_toggle_cost_center(): void
    {
        $cc = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $cc->update(['is_active' => false]);
        $this->assertFalse($cc->fresh()->is_active);

        $cc->update(['is_active' => true]);
        $this->assertTrue($cc->fresh()->is_active);
    }

    public function test_journal_entry_line_belongs_to_cost_center(): void
    {
        $cc = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $account = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $entry = $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'memo' => 'Test with cost center',
            'lines' => [
                ['account_id' => $account->id, 'debit' => 1000, 'credit' => 0, 'cost_center_id' => $cc->id],
                ['account_id' => $account->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $ccLine = JournalEntryLine::where('cost_center_id', $cc->id)->first();
        $this->assertNotNull($ccLine);
        $this->assertEquals($cc->id, $ccLine->cost_center_id);
        $this->assertEquals($cc->id, $ccLine->costCenter->id);
    }

    public function test_cost_center_filter_on_trial_balance(): void
    {
        $cc = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $cash = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $expense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'memo' => 'CC expense',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 5000, 'credit' => 0, 'cost_center_id' => $cc->id],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 5000, 'cost_center_id' => $cc->id],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.trial-balance.index', ['cost_center_id' => $cc->id]));
        $response->assertStatus(200);

        $responseNoCC = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.trial-balance.index'));
        $responseNoCC->assertStatus(200);
    }

    public function test_cost_center_filter_on_general_ledger(): void
    {
        $cc = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $cash = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $expense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'memo' => 'CC expense',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 5000, 'credit' => 0, 'cost_center_id' => $cc->id],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 5000, 'cost_center_id' => $cc->id],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.general-ledger.index', ['cost_center_id' => $cc->id]));
        $response->assertStatus(200);

        $responseNoCC = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.general-ledger.index'));
        $responseNoCC->assertStatus(200);
    }

    public function test_cost_center_filter_on_income_statement(): void
    {
        $cc = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
            'is_active' => true,
        ]);

        $cash = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $revenue = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_revenue',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $expense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'opening_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'memo' => 'Revenue with CC',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 10000, 'credit' => 0, 'cost_center_id' => $cc->id],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 10000, 'cost_center_id' => $cc->id],
            ],
        ]);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => now()->format('Y-m-d'),
            'memo' => 'Expense with CC',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 3000, 'credit' => 0, 'cost_center_id' => $cc->id],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 3000, 'cost_center_id' => $cc->id],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.income-statement.index', ['cost_center_id' => $cc->id]));
        $response->assertStatus(200);

        $responseNoCC = $this->actingAs($this->user)
            ->withSession(['current_company_id' => $this->company->id])
            ->get(route('accounting.income-statement.index'));
        $responseNoCC->assertStatus(200);
    }
}
