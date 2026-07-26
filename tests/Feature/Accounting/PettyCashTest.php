<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Services\Accounting\PettyCashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PettyCashTest extends TestCase
{
    use RefreshDatabase;

    protected PettyCashService $service;
    protected Company $company;
    protected Account $bankAccount;
    protected Account $expenseAccount;
    protected AccountingPeriod $period;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PettyCashService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Office Supplies',
            'type' => 'expense',
            'sub_type' => 'operating',
            'is_active' => true,
        ]);
    }

    public function test_create_fund_creates_petty_cash_account(): void
    {
        $fund = $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1060',
        ], $this->userId);

        $this->assertNotNull($fund->id);
        $this->assertTrue($fund->is_petty_cash);
        $this->assertEquals('1060', $fund->code);
        $this->assertEquals(0, (float) $fund->petty_cash_float);
    }

    public function test_establish_fund_transfers_from_bank(): void
    {
        $fund = $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1060',
        ], $this->userId);

        $bankTx = $this->service->establishFund($fund, $this->bankAccount->id, 500.00, '2026-02-01', $this->userId);

        $this->assertNotNull($bankTx->id);
        $this->assertEquals(-500.00, (float) $bankTx->amount);
        $this->assertEquals('withdrawal', $bankTx->type);

        $fund->refresh();
        $this->assertEquals(500.00, (float) $fund->petty_cash_float);
    }

    public function test_record_expense_decreases_balance(): void
    {
        $fund = $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1060',
        ], $this->userId);

        $this->service->establishFund($fund, $this->bankAccount->id, 500.00, '2026-02-01', $this->userId);

        $result = $this->service->recordExpense([
            'company_id' => $this->company->id,
            'petty_cash_account_id' => $fund->id,
            'debit_account_id' => $this->expenseAccount->id,
            'date' => '2026-02-05',
            'amount' => 50.00,
            'description' => 'Bought pens',
        ], $this->userId);

        $this->assertNotNull($result['journal_entry']);
        $this->assertEquals(450.00, $result['new_balance']);
    }

    public function test_replenish_fund_increases_balance(): void
    {
        $fund = $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1060',
        ], $this->userId);

        $this->service->establishFund($fund, $this->bankAccount->id, 500.00, '2026-02-01', $this->userId);

        $this->service->recordExpense([
            'company_id' => $this->company->id,
            'petty_cash_account_id' => $fund->id,
            'debit_account_id' => $this->expenseAccount->id,
            'date' => '2026-02-05',
            'amount' => 200.00,
            'description' => 'Supplies',
        ], $this->userId);

        $bankTx = $this->service->replenishFund([
            'company_id' => $this->company->id,
            'petty_cash_account_id' => $fund->id,
            'bank_account_id' => $this->bankAccount->id,
            'date' => '2026-02-10',
            'amount' => 200.00,
            'description' => 'Replenish after supplies purchase',
        ], $this->userId);

        $this->assertNotNull($bankTx->id);
        $fund->refresh();
        $this->assertEquals(500.00, (float) $fund->petty_cash_float);
    }

    public function test_get_fund_summary_returns_correct_data(): void
    {
        $fund = $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1060',
        ], $this->userId);

        $this->service->establishFund($fund, $this->bankAccount->id, 500.00, '2026-02-01', $this->userId);

        $summary = $this->service->getFundSummary($this->company->id);

        $this->assertCount(1, $summary);
        $this->assertEquals('Office Petty Cash', $summary[0]['name']);
        $this->assertEquals(500.00, $summary[0]['float']);
    }

    public function test_create_duplicate_fund_name_fails(): void
    {
        $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1060',
        ], $this->userId);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->createFund([
            'company_id' => $this->company->id,
            'name' => 'Office Petty Cash',
            'code' => '1061',
        ], $this->userId);
    }

    public function test_establish_non_petty_cash_account_fails(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->establishFund($this->bankAccount, $this->bankAccount->id, 500.00, '2026-02-01', $this->userId);
    }
}
