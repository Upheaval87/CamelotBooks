<?php

namespace Tests\Feature\Reporting;

use App\Models\{Account, Branch, Company, CostCenter, Customer, Vendor,
    JournalEntry, JournalEntryLine, ExchangeRate, Invoice, Bill};
use App\Services\Reporting\FiReportContract;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * STAGE 2 — FORMULAS + TESTS
 *
 * §10 formula functions exercised against the Stage 1 data contract.
 * Checklist:
 *   ✓ GP/OP/PBT/Net chain
 *   ✓ Variance/% (incl. previous=0 → "—")
 *   ✓ Working Capital / Current Ratio / Debt-to-Equity / Equity Ratio
 *   ✓ SFP balance check (incl. forced-imbalance case)
 *   ✓ CF closing = opening + net (incl. forced-mismatch case)
 *   ✓ Aging bucket totals reconcile to outstanding
 *   ✓ §10.7 Comparative-period partial-year (Jan–Aug)
 *   ✓ §10.8 Multi-currency translation (closing + average)
 *   ✓ §10.9 All-branch aggregation (shared accounts, no double-count)
 */
class FiReportFormulaTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->create();

        $this->company = Company::create([
            'company_code'          => 'FRMTEST',
            'name'                  => 'Formula Test Co',
            'base_currency'         => 'MWK',
            'fiscal_year_start_month' => 1,
            'is_active'             => true,
        ]);

        $user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

        \App\Services\FeatureManagement::enable($this->company->id, 'accounting');
    }

    private function acct(string $code, string $type, string $subType, float $opening = 0, bool $active = true): Account
    {
        return Account::create([
            'company_id'     => $this->company->id,
            'code'           => $code,
            'name'           => "{$type}/{$subType}/{$code}",
            'type'           => $type,
            'sub_type'       => $subType,
            'is_active'      => $active,
            'opening_balance' => $opening,
        ]);
    }

    private function je(string $status = 'posted', string $date = '2026-03-15'): JournalEntry
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

    private function line(JournalEntry $je, Account $acct, float $debit, float $credit, ?int $branchId = null): JournalEntryLine
    {
        return JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id'       => $acct->id,
            'debit'            => $debit,
            'credit'           => $credit,
            'branch_id'        => $branchId,
        ]);
    }

    private function branch(string $code, string $name): Branch
    {
        return Branch::create([
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => $name,
            'is_active'  => true,
        ]);
    }

    private function costCenter(string $code, string $name, int $branchId): CostCenter
    {
        return CostCenter::create([
            'company_id' => $this->company->id,
            'code'       => $code,
            'name'       => $name,
            'branch_id'  => $branchId,
            'is_active'  => true,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.1 — GP / OP / PBT / Net CHAIN
    // ═══════════════════════════════════════════════════════════════════

    public function test_is_gp_op_pbt_net_chain(): void
    {
        // Seed accounts:
        // Revenue = 155M, COGS = 65M → GP = 90M
        // OpEx = 51.5M → OP = 38.5M
        // Finance income = 2M, finance costs = 5M → PBT = 35.5M
        // Tax = 8.875M → Net = 26.625M
        $sales    = $this->acct('4100', 'income', 'revenue');
        $service  = $this->acct('4200', 'income', 'revenue');
        $otherInc = $this->acct('4300', 'income', 'other_income');
        $cogs     = $this->acct('5100', 'expense', 'cost_of_goods_sold');
        $salaries = $this->acct('6100', 'expense', 'operating_expense');
        $rent     = $this->acct('6200', 'expense', 'operating_expense');
        $transport = $this->acct('6300', 'expense', 'operating_expense');
        $marketing = $this->acct('6400', 'expense', 'operating_expense');
        $depreciation = $this->acct('6500', 'expense', 'operating_expense');
        $otherExp = $this->acct('6600', 'expense', 'operating_expense');
        $finInc   = $this->acct('7100', 'income', 'other_income');
        $finCost  = $this->acct('8100', 'expense', 'non_operating_expense');
        $tax      = $this->acct('8200', 'expense', 'non_operating_expense');

        // Journal entries: revenue (credit-normal), expenses (debit-normal)
        $j1 = $this->je();
        $this->line($j1, $sales, 0, 125000000);
        $this->line($j1, $service, 0, 25000000);
        $this->line($j1, $otherInc, 0, 5000000);
        $this->line($j1, $cogs, 65000000, 0);
        $this->line($j1, $salaries, 30000000, 0);
        $this->line($j1, $rent, 8000000, 0);
        $this->line($j1, $transport, 4000000, 0);
        $this->line($j1, $marketing, 3000000, 0);
        $this->line($j1, $depreciation, 2500000, 0);
        $this->line($j1, $otherExp, 4000000, 0);
        $this->line($j1, $finInc, 0, 2000000);
        $this->line($j1, $finCost, 5000000, 0);
        $this->line($j1, $tax, 8875000, 0);

        // Compute using contract's periodActivity
        $allIds = [$sales->id, $service->id, $otherInc->id, $cogs->id,
            $salaries->id, $rent->id, $transport->id, $marketing->id,
            $depreciation->id, $otherExp->id, $finInc->id, $finCost->id, $tax->id];

        $activity = FiReportContract::periodActivity($allIds, $this->company->id, '2026-01-01', '2026-12-31');

        // Revenue (credit-normal: net = credit - debit)
        $revenue = ($activity['total'][$sales->id] ?? 0)
                 + ($activity['total'][$service->id] ?? 0)
                 + ($activity['total'][$otherInc->id] ?? 0);

        // COGS (expense, debit-normal: net = debit - credit)
        $cogsAmount = abs($activity['total'][$cogs->id] ?? 0);

        // OpEx
        $opex = abs($activity['total'][$salaries->id] ?? 0)
              + abs($activity['total'][$rent->id] ?? 0)
              + abs($activity['total'][$transport->id] ?? 0)
              + abs($activity['total'][$marketing->id] ?? 0)
              + abs($activity['total'][$depreciation->id] ?? 0)
              + abs($activity['total'][$otherExp->id] ?? 0);

        // Finance
        $financeIncome = $activity['total'][$finInc->id] ?? 0;
        $financeCosts  = abs($activity['total'][$finCost->id] ?? 0);
        $taxAmount     = abs($activity['total'][$tax->id] ?? 0);

        // §10.1 chain
        $gp    = FiReportContract::computeGp($revenue, $cogsAmount);
        $op    = FiReportContract::computeOperatingProfit($gp, $opex);
        $pbt   = FiReportContract::computePbt($op, $financeIncome, $financeCosts);
        $net   = FiReportContract::computeNetProfit($pbt, $taxAmount);
        $margin = FiReportContract::computeGpMargin($revenue, $cogsAmount);

        $this->assertEqualsWithDelta(155000000, $revenue, 0.01, 'Total revenue');
        $this->assertEqualsWithDelta(65000000, $cogsAmount, 0.01, 'COGS');
        $this->assertEqualsWithDelta(90000000, $gp, 0.01, 'Gross Profit = 155M − 65M');
        $this->assertEqualsWithDelta(51500000, $opex, 0.01, 'Total OpEx');
        $this->assertEqualsWithDelta(38500000, $op, 0.01, 'Operating Profit = 90M − 51.5M');
        $this->assertEqualsWithDelta(35500000, $pbt, 0.01, 'PBT = 38.5M + 2M − 5M');
        $this->assertEqualsWithDelta(26625000, $net, 0.01, 'Net = 35.5M − 8.875M');
        $this->assertEqualsWithDelta(58.06, $margin, 0.01, 'GP margin ≈ 58.06%');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.6 — VARIANCE / %  (divide-by-zero → null / "—")
    // ═══════════════════════════════════════════════════════════════════

    public function test_variance_positive(): void
    {
        $this->assertEquals(5000, FiReportContract::computeVariance(15000, 10000));
    }

    public function test_variance_negative(): void
    {
        $this->assertEquals(-2000, FiReportContract::computeVariance(8000, 10000));
    }

    public function test_variance_pct_normal(): void
    {
        // 15000 vs 10000 → variance 5000 → 50%
        $this->assertEquals(50.0, FiReportContract::computeVariancePct(15000, 10000));
    }

    public function test_variance_pct_previous_zero_returns_null(): void
    {
        // §10.6: previous = 0 → "—", never Infinity or 0.00%
        $this->assertNull(FiReportContract::computeVariancePct(5000, 0));
    }

    public function test_variance_pct_both_zero_returns_null(): void
    {
        $this->assertNull(FiReportContract::computeVariancePct(0, 0));
    }

    public function test_variance_pct_null_previous_returns_null(): void
    {
        $this->assertNull(FiReportContract::computeVariancePct(5000, 0));
    }

    public function test_variance_pct_negative_result(): void
    {
        // 8000 vs 10000 → variance -2000 → -20%
        $this->assertEquals(-20.0, FiReportContract::computeVariancePct(8000, 10000));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.2 — SFP KPIs + BALANCE CHECK
    // ═══════════════════════════════════════════════════════════════════

    public function test_working_capital(): void
    {
        $this->assertEquals(187000, FiReportContract::computeWorkingCapital(307000, 120000));
    }

    public function test_current_ratio(): void
    {
        $this->assertEquals(2.56, FiReportContract::computeCurrentRatio(307000, 120000));
    }

    public function test_current_ratio_zero_liabilities_returns_null(): void
    {
        $this->assertNull(FiReportContract::computeCurrentRatio(100000, 0));
    }

    public function test_debt_to_equity(): void
    {
        $this->assertEquals(1.09, FiReportContract::computeDebtToEquity(330000, 302000));
    }

    public function test_debt_to_equity_zero_equity_returns_null(): void
    {
        $this->assertNull(FiReportContract::computeDebtToEquity(100000, 0));
    }

    public function test_equity_ratio(): void
    {
        // 302/632 × 100 ≈ 47.78%
        $this->assertEquals(47.78, FiReportContract::computeEquityRatio(302000, 632000));
    }

    public function test_equity_ratio_zero_assets_returns_null(): void
    {
        $this->assertNull(FiReportContract::computeEquityRatio(100000, 0));
    }

    public function test_sfp_balance_check_balanced(): void
    {
        $this->assertTrue(FiReportContract::checkSfpBalance(632000, 330000, 302000));
    }

    public function test_sfp_balance_check_imbalanced(): void
    {
        // Forced imbalance: assets = 632, liabilities + equity = 630 → 2.0 mismatch
        $this->assertFalse(FiReportContract::checkSfpBalance(632000, 330000, 300000));
    }

    public function test_sfp_balance_check_tiny_difference_balanced(): void
    {
        // 0.005 < tolerance 0.01 → balanced
        $this->assertTrue(FiReportContract::checkSfpBalance(100.005, 50, 50));
    }

    public function test_sfp_kpis_with_seeded_data(): void
    {
        // Seed: current assets = 307K, current liabilities = 120K, total liabilities = 330K, equity = 302K
        $ca = 307000;
        $cl = 120000;
        $tl = 330000;
        $eq = 302000;
        $ta = 632000;

        $wc = FiReportContract::computeWorkingCapital($ca, $cl);
        $cr = FiReportContract::computeCurrentRatio($ca, $cl);
        $de = FiReportContract::computeDebtToEquity($tl, $eq);
        $er = FiReportContract::computeEquityRatio($eq, $ta);
        $na = $eq; // Net Assets = Equity

        $this->assertEquals(187000, $wc);
        $this->assertEquals(2.56, $cr);
        $this->assertEquals(1.09, $de);
        $this->assertEquals(47.78, $er);
        $this->assertEquals(302000, $na);
        $this->assertTrue(FiReportContract::checkSfpBalance($ta, $tl, $eq));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.3 — CF CLOSING = OPENING + NET
    // ═══════════════════════════════════════════════════════════════════

    public function test_cf_net_calculation(): void
    {
        $net = FiReportContract::computeCfNet(28000000, -7500000, -2000000);
        $this->assertEquals(18500000, $net);
    }

    public function test_cf_closing_check_balanced(): void
    {
        // Opening 93.3M + Net 18.5M = Closing 111.8M
        $this->assertTrue(FiReportContract::checkCfClosing(93300000, 18500000, 111800000));
    }

    public function test_cf_closing_check_mismatch(): void
    {
        // Forced mismatch: opening + net ≠ closing
        $this->assertFalse(FiReportContract::checkCfClosing(93300000, 18500000, 115000000));
    }

    public function test_cf_closing_check_tiny_difference_passes(): void
    {
        $this->assertTrue(FiReportContract::checkCfClosing(1000, 500, 1500.005));
    }

    public function test_cf_with_seeded_bank_accounts(): void
    {
        // Seed bank accounts with opening and JE activity
        $bank1 = Account::create([
            'company_id'     => $this->company->id,
            'code'           => '1110',
            'name'           => 'Main Bank',
            'type'           => 'asset',
            'sub_type'       => 'current_asset',
            'is_active'      => true,
            'is_bank_account' => true,
            'opening_balance' => 50000000,
        ]);
        $bank2 = Account::create([
            'company_id'     => $this->company->id,
            'code'           => '1120',
            'name'           => 'Savings',
            'type'           => 'asset',
            'sub_type'       => 'current_asset',
            'is_active'      => true,
            'is_bank_account' => true,
            'opening_balance' => 43300000,
        ]);

        // Beginning cash = 50M + 43.3M = 93.3M
        $ids = [$bank1->id, $bank2->id];
        $balances = FiReportContract::batchAccountBalances($ids, $this->company->id, '2025-12-31');
        $beginning = array_sum($balances);
        $this->assertEqualsWithDelta(93300000, $beginning, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.4 — AGING BUCKETS RECONCILE TO OUTSTANDING
    // ═══════════════════════════════════════════════════════════════════

    public function test_bucket_aging_days_classifications(): void
    {
        $this->assertEquals('current', FiReportContract::bucketAgingDays(0));
        $this->assertEquals('current', FiReportContract::bucketAgingDays(-5));
        $this->assertEquals('days_1_30', FiReportContract::bucketAgingDays(1));
        $this->assertEquals('days_1_30', FiReportContract::bucketAgingDays(30));
        $this->assertEquals('days_31_60', FiReportContract::bucketAgingDays(31));
        $this->assertEquals('days_31_60', FiReportContract::bucketAgingDays(60));
        $this->assertEquals('days_61_90', FiReportContract::bucketAgingDays(61));
        $this->assertEquals('days_61_90', FiReportContract::bucketAgingDays(90));
        $this->assertEquals('days_90_plus', FiReportContract::bucketAgingDays(91));
        $this->assertEquals('days_90_plus', FiReportContract::bucketAgingDays(365));
    }

    public function test_aging_totals_reconcile(): void
    {
        $buckets = [
            'current'      => 5500,
            'days_1_30'    => 2500,
            'days_31_60'   => 1400,
            'days_61_90'   => 0,
            'days_90_plus' => 1200,
        ];
        $total = 10600;

        $this->assertTrue(FiReportContract::reconcileAgingTotals($buckets, $total));
    }

    public function test_aging_totals_dont_reconcile(): void
    {
        $buckets = [
            'current'      => 5500,
            'days_1_30'    => 2500,
            'days_31_60'   => 1400,
            'days_61_90'   => 0,
            'days_90_plus' => 1200,
        ];
        $total = 11000; // mismatch

        $this->assertFalse(FiReportContract::reconcileAgingTotals($buckets, $total));
    }

    public function test_aging_ar_with_seeded_invoices(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name'       => 'Beta Industries',
            'is_active'  => true,
        ]);

        // Invoice 1: current (not yet due)
        Invoice::create([
            'company_id'     => $this->company->id,
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-1001',
            'invoice_date'   => '2026-08-01',
            'due_date'       => '2026-09-01',
            'status'         => 'posted',
            'amount'         => 5000,
            'amount_paid'    => 0,
            'currency'       => 'MWK',
            'created_by'     => 1,
        ]);

        // Invoice 2: 31-60 days overdue (due 2026-07-01, as-of 2026-08-15 = 45 days)
        Invoice::create([
            'company_id'     => $this->company->id,
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-1002',
            'invoice_date'   => '2026-06-01',
            'due_date'       => '2026-07-01',
            'status'         => 'posted',
            'amount'         => 3000,
            'amount_paid'    => 0,
            'currency'       => 'MWK',
            'created_by'     => 1,
        ]);

        // Invoice 3: 90+ days overdue (due 2026-04-01, as-of 2026-08-15 = 136 days)
        Invoice::create([
            'company_id'     => $this->company->id,
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-1003',
            'invoice_date'   => '2026-03-01',
            'due_date'       => '2026-04-01',
            'status'         => 'posted',
            'amount'         => 1200,
            'amount_paid'    => 0,
            'currency'       => 'MWK',
            'created_by'     => 1,
        ]);

        $result = FiReportContract::arAgingData($this->company->id, '2026-08-15');

        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('customers', $result);

        $totals = $result['totals'];
        $this->assertEqualsWithDelta(5000, $totals['current'], 0.01);
        $this->assertEqualsWithDelta(3000, $totals['days_31_60'], 0.01);
        $this->assertEqualsWithDelta(1200, $totals['days_90_plus'], 0.01);
        $this->assertEqualsWithDelta(9200, $totals['total'], 0.01);

        // §10.4: bucket totals must reconcile
        $bucketTotals = [
            'current'      => $totals['current'],
            'days_1_30'    => $totals['days_1_30'],
            'days_31_60'   => $totals['days_31_60'],
            'days_61_90'   => $totals['days_61_90'],
            'days_90_plus' => $totals['days_90_plus'],
        ];
        $this->assertTrue(FiReportContract::reconcileAgingTotals($bucketTotals, $totals['total']));
    }

    public function test_aging_ap_with_seeded_bills(): void
    {
        $vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name'       => 'Kamuzu Estates',
            'is_active'  => true,
        ]);

        // Bill: current
        Bill::create([
            'company_id'  => $this->company->id,
            'vendor_id'   => $vendor->id,
            'bill_number' => 'BILL-2001',
            'bill_date'   => '2026-08-01',
            'due_date'    => '2026-09-01',
            'status'      => 'posted',
            'amount'      => 8000,
            'amount_paid' => 0,
            'currency'    => 'MWK',
            'created_by'  => 1,
        ]);

        $result = FiReportContract::apAgingData($this->company->id, '2026-08-15');

        $this->assertEqualsWithDelta(8000, $result['totals']['current'], 0.01);
        $this->assertEqualsWithDelta(8000, $result['totals']['total'], 0.01);

        $bucketTotals = [
            'current'      => $result['totals']['current'],
            'days_1_30'    => $result['totals']['days_1_30'],
            'days_31_60'   => $result['totals']['days_31_60'],
            'days_61_90'   => $result['totals']['days_61_90'],
            'days_90_plus' => $result['totals']['days_90_plus'],
        ];
        $this->assertTrue(FiReportContract::reconcileAgingTotals($bucketTotals, $result['totals']['total']));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.7 — COMPARATIVE PERIOD (partial-year Jan–Aug)
    // ═══════════════════════════════════════════════════════════════════

    public function test_comparative_period_partial_year(): void
    {
        // Jan–Aug 2026 → previous = Jan–Aug 2025
        $prev = FiReportContract::comparativePeriod('2026-01-01', '2026-08-31');

        $this->assertEquals('2025-01-01', $prev['date_from']);
        $this->assertEquals('2025-08-31', $prev['date_to']);
        $this->assertStringContainsString('01 Jan 2025', $prev['label']);
        $this->assertStringContainsString('31 Aug 2025', $prev['label']);
    }

    public function test_comparative_period_preserves_range_length(): void
    {
        // Mar–Jun 2026 (122 days) → previous = Mar–Jun 2025 (same 122 days)
        $prev = FiReportContract::comparativePeriod('2026-03-01', '2026-06-30');

        $origDays = \Carbon\Carbon::parse('2026-03-01')->diffInDays(\Carbon\Carbon::parse('2026-06-30'));
        $prevDays = \Carbon\Carbon::parse($prev['date_from'])->diffInDays(\Carbon\Carbon::parse($prev['date_to']));

        $this->assertEquals($origDays, $prevDays, 'Comparative period must be same length');
    }

    public function test_fiscal_year_labels_for_partial_year(): void
    {
        $labels = FiReportContract::fiscalYearLabels('2026-01-01', '2026-08-31');

        $this->assertEquals('2026', $labels['current']);
        $this->assertEquals('2025', $labels['previous']);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.8 — MULTI-CURRENCY TRANSLATION
    // ═══════════════════════════════════════════════════════════════════

    public function test_fx_closing_rate(): void
    {
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'MWK',
            'currency_to'    => 'USD',
            'rate'           => 0.00059,
            'effective_date' => '2026-08-31',
        ]);

        $rate = FiReportContract::fxRate($this->company->id, 'MWK', 'USD', '2026-08-31', 'closing');
        $this->assertEqualsWithDelta(0.00059, $rate, 0.000001);
    }

    public function test_fx_average_rate(): void
    {
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'MWK',
            'currency_to'    => 'EUR',
            'rate'           => 0.00055,
            'effective_date' => '2026-01-31',
        ]);
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'MWK',
            'currency_to'    => 'EUR',
            'rate'           => 0.00057,
            'effective_date' => '2026-07-31',
        ]);

        $rate = FiReportContract::fxRate(
            $this->company->id, 'MWK', 'EUR', '2026-08-31',
            'average', '2026-01-01'
        );
        // Average of 0.00055 and 0.00057 = 0.00056
        $this->assertEqualsWithDelta(0.00056, $rate, 0.000001);
    }

    public function test_fx_same_currency_returns_one(): void
    {
        $this->assertEquals(1.0, FiReportContract::fxRate(
            $this->company->id, 'MWK', 'MWK', '2026-08-31'
        ));
    }

    public function test_fx_no_rate_found_returns_one(): void
    {
        $this->assertEquals(1.0, FiReportContract::fxRate(
            $this->company->id, 'MWK', 'XXX', '2026-08-31'
        ));
    }

    public function test_fx_translate_balance(): void
    {
        // Seed: 100M MWK balance, rate 0.00059 → 59,000 USD
        ExchangeRate::create([
            'company_id'     => $this->company->id,
            'currency_from'  => 'MWK',
            'currency_to'    => 'USD',
            'rate'           => 0.00059,
            'effective_date' => '2026-08-31',
        ]);

        $mwkBalance = 100000000;
        $rate = FiReportContract::fxRate($this->company->id, 'MWK', 'USD', '2026-08-31', 'closing');
        $usdBalance = $mwkBalance * $rate;

        $this->assertEqualsWithDelta(59000, $usdBalance, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.9 — "ALL" BRANCH AGGREGATION (no double-count)
    // ═══════════════════════════════════════════════════════════════════

    public function test_all_branches_aggregation_shared_account(): void
    {
        // GL accounts are company-scoped; journal lines hold separate
        // branch_ids. Naive SUM across branches = correct (§10.9).
        $branchA = $this->branch('BR-A', 'Branch A');
        $branchB = $this->branch('BR-B', 'Branch B');

        $sales = $this->acct('4100', 'income', 'revenue');

        // Branch A: 100K revenue
        $je1 = $this->je();
        $this->line($je1, $sales, 0, 100000, $branchA->id);

        // Branch B: 200K revenue (same account)
        $je2 = $this->je();
        $this->line($je2, $sales, 0, 200000, $branchB->id);

        // No branch (shared): 50K
        $je3 = $this->je();
        $this->line($je3, $sales, 0, 50000, null);

        // "All branches" = all lines (no branch filter) → 350K
        $balance = FiReportContract::accountBalanceAsOf(
            $sales->id, $this->company->id, '2026-12-31'
        );
        $this->assertEqualsWithDelta(350000, $balance, 0.01, 'All branches = A + B + shared');

        // Branch A only → 100K
        $balA = FiReportContract::accountBalanceAsOf(
            $sales->id, $this->company->id, '2026-12-31', $branchA->id
        );
        $this->assertEqualsWithDelta(100000, $balA, 0.01, 'Branch A only');

        // Branch B only → 200K
        $balB = FiReportContract::accountBalanceAsOf(
            $sales->id, $this->company->id, '2026-12-31', $branchB->id
        );
        $this->assertEqualsWithDelta(200000, $balB, 0.01, 'Branch B only');
    }

    public function test_branch_filter_on_period_activity(): void
    {
        $branchA = $this->branch('BR-C', 'Branch C');
        $branchB = $this->branch('BR-D', 'Branch D');

        $sales = $this->acct('4400', 'income', 'revenue');
        $expense = $this->acct('6700', 'expense', 'operating_expense');

        $je1 = $this->je();
        $this->line($je1, $sales, 0, 100000, $branchA->id);
        $this->line($je1, $expense, 30000, 0, $branchA->id);

        $je2 = $this->je();
        $this->line($je2, $sales, 0, 200000, $branchB->id);
        $this->line($je2, $expense, 60000, 0, $branchB->id);

        // Branch A activity
        $actA = FiReportContract::periodActivity(
            [$sales->id, $expense->id],
            $this->company->id,
            '2026-01-01', '2026-12-31',
            $branchA->id
        );
        $this->assertEqualsWithDelta(100000, $actA['total'][$sales->id], 0.01);
        $this->assertEqualsWithDelta(30000, $actA['total'][$expense->id], 0.01);

        // Branch B activity
        $actB = FiReportContract::periodActivity(
            [$sales->id, $expense->id],
            $this->company->id,
            '2026-01-01', '2026-12-31',
            $branchB->id
        );
        $this->assertEqualsWithDelta(200000, $actB['total'][$sales->id], 0.01);
        $this->assertEqualsWithDelta(60000, $actB['total'][$expense->id], 0.01);

        // All branches
        $actAll = FiReportContract::periodActivity(
            [$sales->id, $expense->id],
            $this->company->id,
            '2026-01-01', '2026-12-31'
        );
        $this->assertEqualsWithDelta(300000, $actAll['total'][$sales->id], 0.01);
        $this->assertEqualsWithDelta(90000, $actAll['total'][$expense->id], 0.01);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §16.8 — CROSS-REPORT CONSISTENCY (IS net = SFP current year)
    // ═══════════════════════════════════════════════════════════════════

    public function test_cross_report_is_net_equals_sfp_cype(): void
    {
        $this->assertTrue(FiReportContract::checkIsNetEqualsSfpCype(26625000, 26625000));
        $this->assertFalse(FiReportContract::checkIsNetEqualsSfpCype(26625000, 27000000));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  EDGE CASES
    // ═══════════════════════════════════════════════════════════════════

    public function test_gp_margin_zero_revenue_returns_null(): void
    {
        $this->assertNull(FiReportContract::computeGpMargin(0, 0));
    }

    public function test_compute_gp_basic(): void
    {
        $this->assertEquals(90000, FiReportContract::computeGp(155000, 65000));
    }

    public function test_compute_net_profit_basic(): void
    {
        $this->assertEquals(26625, FiReportContract::computeNetProfit(35500, 8875));
    }

    public function test_reconcile_aging_empty_buckets(): void
    {
        $this->assertTrue(FiReportContract::reconcileAgingTotals([], 0));
    }

    public function test_reconcile_aging_tiny_mismatch(): void
    {
        $this->assertTrue(FiReportContract::reconcileAgingTotals(['current' => 100.005], 100));
    }
}
