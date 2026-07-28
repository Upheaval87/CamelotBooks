<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalYearTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Fiscal Test Co',
            'company_code' => 'FYTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        $this->seedChartOfAccounts($this->company);

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'retained_earnings' => '3100',
            'default_bank' => '1000',
            'default_revenue' => '4000',
            'default_expense' => '5000',
        ];
        foreach ($mappingData as $key => $code) {
            if (isset($accounts[$code])) {
                \App\Models\DefaultAccountMapping::setMapping(
                    $this->company->id, $key, $accounts[$code]->id
                );
            }
        }

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    public function test_create_fiscal_year_generates_12_periods(): void
    {
        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $this->assertEquals('FY2026', $fy->label);
        $this->assertEquals('2026-01-01', $fy->start_date->toDateString());
        $this->assertEquals('2026-12-31', $fy->end_date->toDateString());
        $this->assertEquals('open', $fy->status);
        $this->assertEquals(12, $fy->periods()->count());

        $janPeriod = $fy->periods()->whereDate('start_date', '2026-01-01')->first();
        $this->assertNotNull($janPeriod);
        $this->assertEquals('open', $janPeriod->status);
    }

    public function test_create_fiscal_year_overlapping_throws(): void
    {
        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $service->createFiscalYear($this->company->id, 'FY2026-B', '2026-06-01');
    }

    public function test_close_fiscal_year_posts_closing_entry(): void
    {
        $accounts = Account::where('company_id', $this->company->id)->get();
        $cash = $accounts->firstWhere('code', '1000');
        $salesRevenue = $accounts->firstWhere('code', '4000');

        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $janPeriod = $fy->periods()->whereDate('start_date', '2026-01-01')->first();

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-05',
            'memo' => 'Test revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 50000],
            ],
        ]);

        $fy->periods()->update(['status' => 'closed']);
        $fy->refresh();

        $closingEntry = $service->close($fy, $this->user->id);

        $fy->refresh();
        $this->assertEquals('closed', $fy->status);
        $this->assertNotNull($fy->closing_entry_id);
        $this->assertNotNull($closingEntry);
        $this->assertEquals('year_end_close', $closingEntry->source_module);

        $revenueBalance = $salesRevenue->fresh()->current_balance;
        $this->assertEqualsWithDelta(0, $revenueBalance, 0.01);
    }

    public function test_close_fiscal_year_requires_all_periods_closed(): void
    {
        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All periods');
        $service->close($fy, $this->user->id);
    }

    public function test_reopen_fiscal_year_reverses_closing_entry(): void
    {
        $accounts = Account::where('company_id', $this->company->id)->get();
        $cash = $accounts->firstWhere('code', '1000');
        $salesRevenue = $accounts->firstWhere('code', '4000');

        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-05',
            'memo' => 'Revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 30000, 'credit' => 0],
                ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 30000],
            ],
        ]);

        $fy->periods()->update(['status' => 'closed']);
        $fy->refresh();
        $service->close($fy, $this->user->id);

        $service->reopen($fy, 'Need to correct a mistake in the books', $this->user->id);

        $fy->refresh();
        $this->assertEquals('open', $fy->status);
        $this->assertNull($fy->closing_entry_id);
        $this->assertEquals('Need to correct a mistake in the books', $fy->reopen_reason);
        $this->assertNotNull($fy->reopened_at);

        $reversalCount = JournalEntry::where('company_id', $this->company->id)
            ->where('source_module', 'reversal')
            ->count();
        $this->assertEquals(1, $reversalCount);
    }

    public function test_balance_sheet_cye_zero_after_year_close(): void
    {
        $accounts = Account::where('company_id', $this->company->id)->get();
        $cash = $accounts->firstWhere('code', '1000');
        $salesRevenue = $accounts->firstWhere('code', '4000');

        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);
        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-05',
            'memo' => 'Revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 50000],
            ],
        ]);

        $fy->periods()->update(['status' => 'closed']);
        $fy->refresh();
        $service->close($fy, $this->user->id);

        $bs = app(\App\Services\Reporting\BalanceSheetService::class);
        $result = $bs->generate($this->company->id, null, '2026-06-15');

        $this->assertEqualsWithDelta(0, $result['current_year_earnings'], 0.01);
    }

    public function test_cannot_close_already_closed_year(): void
    {
        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $fy->periods()->update(['status' => 'closed']);
        $fy->refresh();
        $service->close($fy, $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only open');
        $service->close($fy->fresh(), $this->user->id);
    }

    public function test_year_end_close_zeroes_both_income_and_expense_and_bs_balances(): void
    {
        $accounts = Account::where('company_id', $this->company->id)->get();
        $cash = $accounts->firstWhere('code', '1000');
        $salesRevenue = $accounts->firstWhere('code', '4000');
        $rentExpense = $accounts->firstWhere('code', '6100');

        $service = app(\App\Services\Accounting\YearEndCloseService::class);
        $fy = $service->createFiscalYear($this->company->id, 'FY2026', '2026-01-01');

        $engine = app(\App\Services\Accounting\JournalPostingEngine::class);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-05',
            'memo' => 'Revenue',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $salesRevenue->id, 'debit' => 0, 'credit' => 50000],
            ],
        ]);

        $engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-01-10',
            'memo' => 'Rent',
            'lines' => [
                ['account_id' => $rentExpense->id, 'debit' => 12000, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 12000],
            ],
        ]);

        $fy->periods()->update(['status' => 'closed']);
        $fy->refresh();
        $service->close($fy, $this->user->id);

        $this->assertEqualsWithDelta(0, $salesRevenue->fresh()->current_balance, 0.01);
        $this->assertEqualsWithDelta(0, $rentExpense->fresh()->current_balance, 0.01);

        $retainedEarnings = $accounts->firstWhere('code', '3100');
        $this->assertNotNull($retainedEarnings);
        $this->assertEqualsWithDelta(38000, $retainedEarnings->fresh()->current_balance, 0.01);
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
            ['code' => '6100', 'name' => 'Rent Expense', 'type' => 'expense', 'sub_type' => 'operating_expense'],
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
