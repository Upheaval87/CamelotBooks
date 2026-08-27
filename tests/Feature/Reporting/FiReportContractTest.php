<?php

namespace Tests\Feature\Reporting;

use App\Models\{Account, Branch, Company, CostCenter, JournalEntry, JournalEntryLine, ExchangeRate};
use App\Services\Reporting\FiReportContract;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FiReportContractTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->create();

        $this->company = Company::create([
            'company_code'          => 'FICTEST',
            'name'                  => 'FiReportContract Test Co',
            'base_currency'         => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active'             => true,
        ]);

        $user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        \App\Services\FeatureManagement::enable($this->company->id, 'accounting');
    }

    private function makeAccount(array $overrides = []): Account
    {
        return Account::create(array_merge([
            'company_id' => $this->company->id,
            'code'       => rand(1000, 9999),
            'name'       => 'Test Account',
            'is_active'  => true,
        ], $overrides));
    }

    private function makeJE(string $status = 'posted', string $date = '2026-03-15'): JournalEntry
    {
        return JournalEntry::create([
            'company_id'     => $this->company->id,
            'journal_number' => 'JE-' . rand(1000, 9999),
            'status'         => $status,
            'date'           => $date,
            'total_debit'    => 0,
            'total_credit'   => 0,
            'created_by'     => 1,
        ]);
    }

    // ─── PERIOD RESOLUTION ──────────────────────────────────────────

    public function test_resolve_period_with_explicit_dates(): void
    {
        $result = FiReportContract::resolvePeriod(
            $this->company->id, '2026-01-01', '2026-06-30'
        );

        $this->assertEquals('2026-01-01', $result['date_from']);
        $this->assertEquals('2026-06-30', $result['date_to']);
        $this->assertStringContainsString('01 Jan 2026', $result['label']);
        $this->assertStringContainsString('30 Jun 2026', $result['label']);
    }

    public function test_comparative_period_shifts_one_year(): void
    {
        $result = FiReportContract::comparativePeriod('2026-01-01', '2026-06-30');

        $this->assertEquals('2025-01-01', $result['date_from']);
        $this->assertEquals('2025-06-30', $result['date_to']);
    }

    public function test_fiscal_year_labels(): void
    {
        $labels = FiReportContract::fiscalYearLabels('2026-01-01', '2026-12-31');

        $this->assertEquals('2026', $labels['current']);
        $this->assertEquals('2025', $labels['previous']);
    }

    // ─── BALANCE FUNCTIONS ───────────────────────────────────────────

    public function test_account_balance_as_of_basic(): void
    {
        $account = $this->makeAccount([
            'type'            => 'asset',
            'sub_type'        => 'current_asset',
            'opening_balance' => 1000,
        ]);

        $je = $this->makeJE();
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $account->id,
            'debit'            => 500,
            'credit'           => 0,
        ]);

        $balance = FiReportContract::accountBalanceAsOf(
            $account->id, $this->company->id, '2026-06-30'
        );

        // 1000 opening + 500 debit (asset = debit-normal) = 1500
        $this->assertEquals(1500.0, $balance);
    }

    public function test_account_balance_excludes_draft_entries(): void
    {
        $account = $this->makeAccount([
            'type'            => 'asset',
            'sub_type'        => 'current_asset',
            'opening_balance' => 1000,
        ]);

        // Posted entry
        $je1 = $this->makeJE('posted', '2026-03-15');
        JournalEntryLine::create([
            'journal_entry_id' => $je1->id,
            'account_id'       => $account->id,
            'debit'            => 500,
            'credit'           => 0,
        ]);

        // Draft entry (should NOT affect balance)
        $je2 = $this->makeJE('draft', '2026-04-01');
        JournalEntryLine::create([
            'journal_entry_id' => $je2->id,
            'account_id'       => $account->id,
            'debit'            => 9999,
            'credit'           => 0,
        ]);

        $balance = FiReportContract::accountBalanceAsOf(
            $account->id, $this->company->id, '2026-06-30'
        );

        // 1000 + 500 = 1500 (draft excluded)
        $this->assertEquals(1500.0, $balance);
    }

    public function test_account_balance_income_credit_normal(): void
    {
        $account = $this->makeAccount([
            'type'            => 'income',
            'sub_type'        => 'revenue',
            'opening_balance' => 0,
        ]);

        $je = $this->makeJE();
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $account->id,
            'debit'            => 0,
            'credit'           => 500,
        ]);

        $balance = FiReportContract::accountBalanceAsOf(
            $account->id, $this->company->id, '2026-06-30'
        );

        // Income (credit-normal): 0 + 500 = 500
        $this->assertEquals(500.0, $balance);
    }

    public function test_batch_account_balances_multiple_accounts(): void
    {
        $acc1 = $this->makeAccount([
            'code'            => '1100',
            'name'            => 'AR',
            'type'            => 'asset',
            'sub_type'        => 'current_asset',
            'opening_balance' => 1000,
        ]);
        $acc2 = $this->makeAccount([
            'code'            => '4100',
            'name'            => 'Sales',
            'type'            => 'income',
            'sub_type'        => 'revenue',
            'opening_balance' => 0,
        ]);

        $je = $this->makeJE();
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $acc1->id,
            'debit'            => 200,
            'credit'           => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $acc2->id,
            'debit'            => 0,
            'credit'           => 300,
        ]);

        $balances = FiReportContract::batchAccountBalances(
            [$acc1->id, $acc2->id],
            $this->company->id,
            '2026-06-30'
        );

        $this->assertEquals(1200.0, $balances[$acc1->id]); // 1000 + 200
        $this->assertEquals(300.0, $balances[$acc2->id]);  // 0 + 300 (credit-normal)
    }

    public function test_batch_account_balances_empty_ids_returns_empty(): void
    {
        $balances = FiReportContract::batchAccountBalances([], $this->company->id, '2026-06-30');
        $this->assertEmpty($balances);
    }

    // ─── PERIOD ACTIVITY ─────────────────────────────────────────────

    public function test_period_activity_income_and_expense(): void
    {
        $incomeAcc = $this->makeAccount([
            'code'     => '4200',
            'name'     => 'Service Revenue',
            'type'     => 'income',
            'sub_type' => 'revenue',
        ]);
        $expenseAcc = $this->makeAccount([
            'code'     => '5100',
            'name'     => 'Salaries',
            'type'     => 'expense',
            'sub_type' => 'operating_expense',
        ]);

        $je = $this->makeJE();
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $incomeAcc->id,
            'debit'            => 0,
            'credit'           => 500,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $expenseAcc->id,
            'debit'            => 300,
            'credit'           => 0,
        ]);

        $result = FiReportContract::periodActivity(
            [$incomeAcc->id, $expenseAcc->id],
            $this->company->id,
            '2026-01-01', '2026-06-30'
        );

        $this->assertEquals(500.0, $result['total'][$incomeAcc->id]);
        $this->assertEquals(300.0, $result['total'][$expenseAcc->id]);
        $this->assertEquals(0, $result['breakdown'][$incomeAcc->id]['debit']);
        $this->assertEquals(500, $result['breakdown'][$incomeAcc->id]['credit']);
    }

    public function test_period_activity_respects_date_range(): void
    {
        $incomeAcc = $this->makeAccount([
            'code'     => '4300',
            'name'     => 'Range Revenue',
            'type'     => 'income',
            'sub_type' => 'revenue',
        ]);

        // Entry in range
        $je1 = $this->makeJE('posted', '2026-03-15');
        JournalEntryLine::create([
            'journal_entry_id' => $je1->id,
            'account_id'       => $incomeAcc->id,
            'debit'            => 0,
            'credit'           => 100,
        ]);

        // Entry OUTSIDE range
        $je2 = $this->makeJE('posted', '2026-08-01');
        JournalEntryLine::create([
            'journal_entry_id' => $je2->id,
            'account_id'       => $incomeAcc->id,
            'debit'            => 0,
            'credit'           => 999,
        ]);

        $result = FiReportContract::periodActivity(
            [$incomeAcc->id],
            $this->company->id,
            '2026-01-01', '2026-06-30'
        );

        $this->assertEquals(100.0, $result['total'][$incomeAcc->id]);
    }

    public function test_period_activity_empty_accounts(): void
    {
        $result = FiReportContract::periodActivity(
            [],
            $this->company->id,
            '2026-01-01', '2026-06-30'
        );

        $this->assertEmpty($result['total']);
        $this->assertEmpty($result['breakdown']);
    }

    // ─── SAFE PERCENTAGE ─────────────────────────────────────────────

    public function test_safe_pct_returns_null_when_denominator_zero(): void
    {
        $this->assertNull(FiReportContract::safePct(100, 0));
        $this->assertNull(FiReportContract::safePct(100, null));
    }

    public function test_safe_pct_computes_correctly(): void
    {
        // safePct returns (numerator / |denominator|) * 100
        // e.g. variance of 50 on base of 100 → 50%
        $this->assertEquals(50.0, FiReportContract::safePct(50, 100));
        $this->assertEquals(-25.0, FiReportContract::safePct(-25, 100));
        $this->assertEquals(150.0, FiReportContract::safePct(150, 100));
    }

    // ─── §0.6 SCOPE: POSTED-ONLY RULE ────────────────────────────────

    public function test_gl_reportable_statuses_include_posted_and_reversed(): void
    {
        $statuses = FiReportContract::GL_REPORTABLE_STATUSES;

        $this->assertContains('posted', $statuses);
        $this->assertContains('reversed', $statuses);
        $this->assertNotContains('draft', $statuses);
        $this->assertNotContains('pending_approval', $statuses);
        $this->assertNotContains('approved', $statuses);
    }

    public function test_balance_includes_reversed_entries(): void
    {
        $account = $this->makeAccount([
            'type'            => 'asset',
            'sub_type'        => 'current_asset',
            'opening_balance' => 500,
        ]);

        // Original posted entry: +200 debit
        $je1 = $this->makeJE('posted', '2026-02-01');
        JournalEntryLine::create([
            'journal_entry_id' => $je1->id,
            'account_id'       => $account->id,
            'debit'            => 200,
            'credit'           => 0,
        ]);

        // Reversal: -200 credit (reversed = included in scope)
        $je2 = $this->makeJE('reversed', '2026-02-15');
        JournalEntryLine::create([
            'journal_entry_id' => $je2->id,
            'account_id'       => $account->id,
            'debit'            => 0,
            'credit'           => 200,
        ]);

        $balance = FiReportContract::accountBalanceAsOf(
            $account->id, $this->company->id, '2026-06-30'
        );

        // 500 opening + 200 - 200 = 500
        $this->assertEquals(500.0, $balance);
    }

    // ─── FX TRANSLATION ──────────────────────────────────────────────

    public function test_fx_rate_returns_one_when_same_currency(): void
    {
        $rate = FiReportContract::fxRate(
            $this->company->id, 'USD', 'USD', '2026-06-30'
        );

        $this->assertEquals(1.0, $rate);
    }

    public function test_fx_rate_closing_returns_rate(): void
    {
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'USD',
            'currency_to'    => 'EUR',
            'rate'           => 0.85,
            'effective_date' => '2026-06-30',
        ]);

        // Verify the record actually exists in the DB
        $found = ExchangeRate::where('company_id', $this->company->id)
            ->where('currency_from', 'USD')
            ->where('currency_to', 'EUR')
            ->first();
        $this->assertNotNull($found, 'ExchangeRate record not found in DB');

        $rate = FiReportContract::fxRate(
            $this->company->id, 'USD', 'EUR', '2026-06-30'
        );

        $this->assertEquals(0.85, $rate);
    }

    public function test_fx_rate_average_uses_avg(): void
    {
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'USD',
            'currency_to'    => 'GBP',
            'rate'           => 0.70,
            'effective_date' => '2026-01-15',
        ]);
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'USD',
            'currency_to'    => 'GBP',
            'rate'           => 0.80,
            'effective_date' => '2026-06-15',
        ]);

        $rate = FiReportContract::fxRate(
            $this->company->id, 'USD', 'GBP', '2026-06-30',
            'average', '2026-01-01'
        );

        $this->assertEqualsWithDelta(0.75, $rate, 0.001);
    }

    public function test_fx_rate_returns_one_when_no_rate_found(): void
    {
        $rate = FiReportContract::fxRate(
            $this->company->id, 'USD', 'XXX', '2026-06-30'
        );

        $this->assertEquals(1.0, $rate);
    }

    // ─── FORMATTING ──────────────────────────────────────────────────

    public function test_fmt_parens_wraps_negatives(): void
    {
        $this->assertStringStartsWith('(', FiReportContract::fmtParens(-100));
        $result0 = FiReportContract::fmtParens(0);
        $this->assertStringStartsWith('0', $result0);
        $resultPos = FiReportContract::fmtParens(100);
        $this->assertStringStartsWith('1', $resultPos);
    }

    public function test_fmt_returns_number(): void
    {
        $result = FiReportContract::fmt(1234.56);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ─── LOOKUP HELPERS ──────────────────────────────────────────────

    public function test_branches_returns_active_for_company(): void
    {
        Branch::create([
            'company_id' => $this->company->id,
            'code'       => 'BR-A',
            'name'       => 'Branch A',
            'is_active'  => true,
        ]);
        Branch::create([
            'company_id' => $this->company->id,
            'code'       => 'BR-B',
            'name'       => 'Branch B',
            'is_active'  => false,
        ]);

        $branches = FiReportContract::branches($this->company->id);

        $this->assertCount(1, $branches);
        $this->assertEquals('Branch A', $branches->first()->name);
    }

    public function test_cost_centers_returns_active(): void
    {
        CostCenter::create([
            'company_id' => $this->company->id,
            'code'       => 'CC-01',
            'name'       => 'Operations',
            'is_active'  => true,
        ]);

        $ccs = FiReportContract::costCenters($this->company->id);

        $this->assertGreaterThanOrEqual(1, $ccs->count());
    }

    // ─── BATCH COMPARATIVE ───────────────────────────────────────────

    public function test_batch_comparative_balances(): void
    {
        $acc = $this->makeAccount([
            'code'            => '1500',
            'name'            => 'Comparative Test',
            'type'            => 'asset',
            'sub_type'        => 'current_asset',
            'opening_balance' => 100,
        ]);

        $je = $this->makeJE('posted', '2025-06-15');
        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $acc->id,
            'debit'            => 200,
            'credit'           => 0,
        ]);

        $result = FiReportContract::batchComparativeBalances(
            [$acc->id],
            $this->company->id,
            '2026-06-30',
            '2025-06-30'
        );

        // Current (2026-06-30): 100 + 200 = 300
        $this->assertEquals(300.0, $result['current'][$acc->id]);
        // Previous (2025-06-30): 100 + 200 = 300 (same entry is before both dates)
        $this->assertEquals(300.0, $result['previous'][$acc->id]);
    }
}
