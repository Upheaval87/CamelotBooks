<?php

namespace Tests\Unit\Services\VendorCentre;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Models\VendorPayment;
use App\Services\Reporting\AgingReportService;
use App\Services\VendorCentre\VendorCentreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorCentreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Vendor $vendorA;
    protected Vendor $vendorB;
    protected Account $apAccount;
    protected Account $bankAccount;
    protected VendorCentreService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'VCSVC',
            'name' => 'VC Service Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->apAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1010',
            'name' => 'Main Bank Account',
            'type' => 'asset',
            'sub_type' => 'bank_account',
            'is_active' => true,
            'is_bank_account' => true,
        ]);

        $expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->vendorA = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Supplies',
            'is_active' => true,
            'opening_balance' => 500.00,
        ]);

        $this->vendorB = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Beta Parts',
            'is_active' => true,
            'opening_balance' => 200.00,
        ]);

        $this->svc = app(VendorCentreService::class);
        $this->svc->clearCache();
    }

    /* ── Helper: create a bill ───────────────────────────────── */

    private function makeBill(array $overrides = []): Bill
    {
        return Bill::create(array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendorA->id,
            'bill_number' => 'BILL-' . mt_rand(1000, 9999),
            'bill_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'amount' => 1000.00,
            'amount_paid' => 0,
            'status' => Bill::STATUS_APPROVED,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    private function makePayment(array $overrides = []): VendorPayment
    {
        return VendorPayment::create(array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendorA->id,
            'payment_number' => 'PAY-' . mt_rand(1000, 9999),
            'payment_date' => now()->toDateString(),
            'amount' => 500.00,
            'status' => VendorPayment::STATUS_POSTED,
            'bank_account_id' => $this->bankAccount->id,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    private function makeCredit(array $overrides = []): VendorCredit
    {
        return VendorCredit::create(array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendorA->id,
            'credit_note_number' => 'VC-' . mt_rand(1000, 9999),
            'credit_note_date' => now()->toDateString(),
            'amount' => 200.00,
            'status' => VendorCredit::STATUS_POSTED,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    private function makePO(array $overrides = []): PurchaseOrder
    {
        return PurchaseOrder::create(array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendorA->id,
            'po_number' => 'PO-' . mt_rand(1000, 9999),
            'date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    /* ═══════════════════════════════════════════════════════════
       getAgingBuckets
       ═══════════════════════════════════════════════════════════ */

    public function test_aging_buckets_empty_when_no_bills(): void
    {
        $result = $this->svc->getAgingBuckets($this->company->id);

        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('vendors', $result);
        $this->assertEquals(0, $result['totals']['total']);
    }

    public function test_aging_buckets_caches_result(): void
    {
        $this->makeBill(['amount' => 500, 'due_date' => now()->subDays(5)->toDateString()]);

        $first = $this->svc->getAgingBuckets($this->company->id);
        $second = $this->svc->getAgingBuckets($this->company->id);

        $this->assertSame($first, $second);
    }

    public function test_aging_buckets_clear_cache(): void
    {
        $this->makeBill(['amount' => 500, 'due_date' => now()->subDays(5)->toDateString()]);

        $this->svc->getAgingBuckets($this->company->id);
        $this->svc->clearCache();

        $result = $this->svc->getAgingBuckets($this->company->id);
        $this->assertGreaterThan(0, $result['totals']['total']);
    }

    public function test_aging_buckets_ages_into_correct_bucket(): void
    {
        $this->makeBill(['amount' => 200, 'due_date' => now()->toDateString(), 'status' => 'approved']);
        $this->makeBill(['amount' => 300, 'due_date' => now()->subDays(15)->toDateString(), 'status' => 'approved']);
        $this->makeBill(['amount' => 400, 'due_date' => now()->subDays(45)->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getAgingBuckets($this->company->id);
        $totals = $result['totals'];

        $this->assertEqualsWithDelta(200, $totals['current'], 0.01);
        $this->assertEqualsWithDelta(300, $totals['days_1_30'], 0.01);
        $this->assertEqualsWithDelta(400, $totals['days_31_60'], 0.01);
        $this->assertEqualsWithDelta(900, $totals['total'], 0.01);
    }

    /* ═══════════════════════════════════════════════════════════
       getTotalPayables
       ═══════════════════════════════════════════════════════════ */

    public function test_total_payables_zero_empty(): void
    {
        $this->assertEquals(0, $this->svc->getTotalPayables($this->company->id));
    }

    public function test_total_payables_sums_aging(): void
    {
        $this->makeBill(['amount' => 1000, 'amount_paid' => 200, 'due_date' => now()->addDays(5)->toDateString(), 'status' => 'approved']);
        $this->makeBill(['amount' => 500, 'amount_paid' => 0, 'due_date' => now()->addDays(10)->toDateString(), 'status' => 'approved']);

        $total = $this->svc->getTotalPayables($this->company->id);

        $this->assertEqualsWithDelta(1300, $total, 0.01);
    }

    /* ═══════════════════════════════════════════════════════════
       getAgingBarData
       ═══════════════════════════════════════════════════════════ */

    public function test_aging_bar_data_structure(): void
    {
        $bars = $this->svc->getAgingBarData($this->company->id);

        $this->assertCount(6, $bars);
        foreach ($bars as $bar) {
            $this->assertArrayHasKey('label', $bar);
            $this->assertArrayHasKey('amount', $bar);
            $this->assertArrayHasKey('color', $bar);
            $this->assertArrayHasKey('pct', $bar);
            $this->assertIsNumeric($bar['amount']);
            $this->assertIsNumeric($bar['pct']);
        }
    }

    public function test_aging_bar_data_pct_calculation(): void
    {
        $this->makeBill(['amount' => 1000, 'due_date' => now()->addDays(5)->toDateString(), 'status' => 'approved']);
        $this->makeBill(['amount' => 200, 'due_date' => now()->subDays(10)->toDateString(), 'status' => 'approved']);

        $bars = $this->svc->getAgingBarData($this->company->id);

        $maxPct = max(array_column($bars, 'pct'));
        $this->assertEqualsWithDelta(100.0, $maxPct, 0.1);
    }

    public function test_aging_bar_data_zero_bills_no_division_error(): void
    {
        $bars = $this->svc->getAgingBarData($this->company->id);

        foreach ($bars as $bar) {
            $this->assertEquals(0.0, $bar['amount']);
            $this->assertEquals(0.0, $bar['pct']);
        }
    }

    /* ═══════════════════════════════════════════════════════════
       getDueThisWeek
       ═══════════════════════════════════════════════════════════ */

    public function test_due_this_week_empty(): void
    {
        $result = $this->svc->getDueThisWeek($this->company->id);

        $this->assertEquals(0, $result['count']);
        $this->assertEquals(0, $result['total_amount']);
        $this->assertEmpty($result['payments']);
    }

    public function test_due_this_week_includes_bills_due_soon(): void
    {
        $this->makeBill(['amount' => 800, 'due_date' => now()->addDays(3)->toDateString(), 'status' => 'approved']);
        $this->makeBill(['amount' => 300, 'due_date' => now()->addDays(6)->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getDueThisWeek($this->company->id);

        $this->assertEquals(2, $result['count']);
        $this->assertEqualsWithDelta(1100, $result['total_amount'], 0.01);
    }

    public function test_due_this_week_excludes_overdue(): void
    {
        $this->makeBill(['amount' => 400, 'due_date' => now()->subDays(5)->toDateString(), 'status' => 'overdue']);

        $result = $this->svc->getDueThisWeek($this->company->id);

        $this->assertEquals(0, $result['count']);
    }

    public function test_due_this_week_excludes_bills_due_beyond_7_days(): void
    {
        $this->makeBill(['amount' => 500, 'due_date' => now()->addDays(7)->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getDueThisWeek($this->company->id);

        $this->assertEquals(0, $result['count']);
    }

    public function test_due_this_week_only_open_statuses(): void
    {
        $this->makeBill(['amount' => 500, 'due_date' => now()->addDays(3)->toDateString(), 'status' => 'paid']);
        $this->makeBill(['amount' => 500, 'due_date' => now()->addDays(3)->toDateString(), 'status' => 'void']);

        $result = $this->svc->getDueThisWeek($this->company->id);

        $this->assertEquals(0, $result['count']);
    }

    public function test_due_this_week_sorted_by_due_date(): void
    {
        $this->makeBill(['amount' => 100, 'due_date' => now()->addDays(5)->toDateString(), 'status' => 'approved']);
        $this->makeBill(['amount' => 200, 'due_date' => now()->addDays(2)->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getDueThisWeek($this->company->id);

        $this->assertLessThan(
            Carbon::parse($result['payments'][1]['due_date'])->timestamp,
            Carbon::parse($result['payments'][0]['due_date'])->timestamp
        );
    }

    /* ═══════════════════════════════════════════════════════════
       getOverdueStats
       ═══════════════════════════════════════════════════════════ */

    public function test_overdue_stats_empty(): void
    {
        $result = $this->svc->getOverdueStats($this->company->id);

        $this->assertEquals(0, $result['amount']);
        $this->assertEquals(0, $result['vendor_count']);
    }

    public function test_overdue_stats_counts_overdue_amount(): void
    {
        $this->makeBill([
            'amount' => 800,
            'amount_paid' => 0,
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => 'approved',
        ]);

        $result = $this->svc->getOverdueStats($this->company->id);

        $this->assertGreaterThan(0, $result['amount']);
        $this->assertEquals(1, $result['vendor_count']);
    }

    public function test_overdue_stats_distinct_vendors(): void
    {
        $this->makeBill([
            'vendor_id' => $this->vendorA->id,
            'amount' => 400,
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => 'approved',
        ]);
        $this->makeBill([
            'vendor_id' => $this->vendorA->id,
            'amount' => 300,
            'due_date' => now()->subDays(3)->toDateString(),
            'status' => 'approved',
        ]);
        $this->makeBill([
            'vendor_id' => $this->vendorB->id,
            'amount' => 600,
            'due_date' => now()->subDays(8)->toDateString(),
            'status' => 'approved',
        ]);

        $result = $this->svc->getOverdueStats($this->company->id);

        $this->assertEquals(2, $result['vendor_count']);
    }

    /* ═══════════════════════════════════════════════════════════
       getPurchasesYTD
       ═══════════════════════════════════════════════════════════ */

    public function test_purchases_ytd_zero_empty(): void
    {
        $result = $this->svc->getPurchasesYTD($this->company->id);

        $this->assertEquals(0, $result['ytd']);
        $this->assertEquals(0, $result['last_year']);
        $this->assertEquals(0, $result['pct_change']);
    }

    public function test_purchases_ytd_includes_this_year_bills(): void
    {
        $this->makeBill([
            'amount' => 5000,
            'bill_date' => now()->subMonth()->toDateString(),
            'status' => 'approved',
        ]);

        $result = $this->svc->getPurchasesYTD($this->company->id);

        $this->assertGreaterThanOrEqual(5000, $result['ytd']);
    }

    public function test_purchases_ytd_excludes_draft_bills(): void
    {
        $this->makeBill([
            'amount' => 5000,
            'bill_date' => now()->subMonth()->toDateString(),
            'status' => 'draft',
        ]);

        $result = $this->svc->getPurchasesYTD($this->company->id);

        $this->assertEquals(0, $result['ytd']);
    }

    public function test_purchases_ytd_calc_pct_change_vs_last_year(): void
    {
        $this->makeBill([
            'amount' => 10000,
            'bill_date' => now()->toDateString(),
            'status' => 'approved',
        ]);
        $this->makeBill([
            'amount' => 5000,
            'bill_date' => now()->subYear()->toDateString(),
            'status' => 'approved',
        ]);

        $result = $this->svc->getPurchasesYTD($this->company->id);

        $this->assertGreaterThan(0, $result['ytd']);
        $this->assertGreaterThan(0, $result['pct_change']);
    }

    /* ═══════════════════════════════════════════════════════════
       getUpcomingPayments
       ═══════════════════════════════════════════════════════════ */

    public function test_upcoming_payments_empty(): void
    {
        $result = $this->svc->getUpcomingPayments($this->company->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_upcoming_payments_includes_30_day_window(): void
    {
        $this->makeBill(['amount' => 700, 'due_date' => now()->addDays(15)->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getUpcomingPayments($this->company->id);

        $this->assertCount(1, $result);
        $this->assertEquals(700, $result[0]['amount']);
    }

    public function test_upcoming_payments_includes_severity_dot(): void
    {
        $this->makeBill(['amount' => 500, 'due_date' => now()->addDays(1)->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getUpcomingPayments($this->company->id);

        $this->assertArrayHasKey('dot_color', $result[0]);
        $this->assertNotEmpty($result[0]['dot_color']);
    }

    public function test_upcoming_payments_includes_due_label(): void
    {
        $this->makeBill(['amount' => 500, 'due_date' => now()->toDateString(), 'status' => 'approved']);

        $result = $this->svc->getUpcomingPayments($this->company->id);

        $this->assertEquals('Today', $result[0]['due_label']);
    }

    public function test_upcoming_payments_excludes_overdue_bills(): void
    {
        $this->makeBill(['amount' => 400, 'due_date' => now()->subDays(5)->toDateString(), 'status' => 'overdue']);

        $result = $this->svc->getUpcomingPayments($this->company->id);

        $this->assertEmpty($result);
    }

    public function test_upcoming_payments_limit_10(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->makeBill([
                'amount' => 100,
                'bill_number' => 'LIM-' . $i,
                'due_date' => now()->addDays(1 + $i)->toDateString(),
                'status' => 'approved',
            ]);
        }

        $result = $this->svc->getUpcomingPayments($this->company->id);

        $this->assertLessThanOrEqual(10, count($result));
    }

    /* ═══════════════════════════════════════════════════════════
       getTopVendors
       ═══════════════════════════════════════════════════════════ */

    public function test_top_vendors_empty(): void
    {
        Vendor::query()->where('company_id', $this->company->id)->delete();
        $result = $this->svc->getTopVendors($this->company->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_top_vendors_sort_by_spend(): void
    {
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 5000, 'status' => 'approved']);
        $this->makeBill(['vendor_id' => $this->vendorB->id, 'amount' => 1000, 'status' => 'approved']);

        $result = $this->svc->getTopVendors($this->company->id, 'spend');

        $this->assertGreaterThanOrEqual($result[1]['purchases'], $result[0]['purchases']);
    }

    public function test_top_vendors_sort_by_outstanding(): void
    {
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 1000, 'amount_paid' => 800, 'status' => 'approved']);
        $this->makeBill(['vendor_id' => $this->vendorB->id, 'amount' => 5000, 'amount_paid' => 0, 'status' => 'approved']);

        $result = $this->svc->getTopVendors($this->company->id, 'out');

        $this->assertGreaterThanOrEqual($result[1]['outstanding'], $result[0]['outstanding']);
    }

    public function test_top_vendors_sort_by_transaction_count(): void
    {
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 100, 'bill_number' => 'BV1', 'status' => 'approved']);
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 100, 'bill_number' => 'BV2', 'status' => 'approved']);
        $this->makeBill(['vendor_id' => $this->vendorB->id, 'amount' => 500, 'bill_number' => 'BV3', 'status' => 'approved']);

        $result = $this->svc->getTopVendors($this->company->id, 'count');

        $this->assertGreaterThanOrEqual($result[1]['transactions'], $result[0]['transactions']);
    }

    public function test_top_vendors_limit_5(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $v = Vendor::create([
                'company_id' => $this->company->id,
                'name' => "Vendor $i",
                'is_active' => true,
            ]);
            $this->makeBill(['vendor_id' => $v->id, 'amount' => 100 * ($i + 1), "bill_number" => "V{$i}B1", 'status' => 'approved']);
        }

        $result = $this->svc->getTopVendors($this->company->id, 'spend', 5);

        $this->assertLessThanOrEqual(5, count($result));
    }

    public function test_top_vendors_excludes_inactive(): void
    {
        $inactive = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Ghost Vendor',
            'is_active' => false,
        ]);
        $this->makeBill(['vendor_id' => $inactive->id, 'amount' => 999999, 'status' => 'approved']);

        $result = $this->svc->getTopVendors($this->company->id, 'spend');

        foreach ($result as $row) {
            $this->assertNotEquals('Ghost Vendor', $row['vendor_name']);
        }
    }

    public function test_top_vendors_has_required_keys(): void
    {
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 500, 'status' => 'approved']);

        $result = $this->svc->getTopVendors($this->company->id);

        $this->assertArrayHasKey('vendor_id', $result[0]);
        $this->assertArrayHasKey('vendor_name', $result[0]);
        $this->assertArrayHasKey('purchases', $result[0]);
        $this->assertArrayHasKey('outstanding', $result[0]);
        $this->assertArrayHasKey('transactions', $result[0]);
        $this->assertArrayHasKey('last_purchase', $result[0]);
    }

    /* ═══════════════════════════════════════════════════════════
       getPendingTransactions
       ═══════════════════════════════════════════════════════════ */

    public function test_pending_transactions_empty(): void
    {
        $result = $this->svc->getPendingTransactions($this->company->id);

        $this->assertIsArray($result);
        $this->assertCount(6, $result);
        foreach ($result as $row) {
            $this->assertEquals(0, $row['count']);
        }
    }

    public function test_pending_transactions_counts_draft_pos(): void
    {
        $this->makePO(['status' => 'draft']);
        $this->makePO(['status' => 'draft', 'po_number' => 'PO-2']);

        $result = $this->svc->getPendingTransactions($this->company->id);

        $poDraft = collect($result)->firstWhere('status', 'Awaiting Approval')['count'];
        $this->assertEquals(2, $poDraft);
    }

    public function test_pending_transactions_counts_pending_bills(): void
    {
        $this->makeBill(['status' => 'pending_approval']);

        $result = $this->svc->getPendingTransactions($this->company->id);

        $row = collect($result)->firstWhere(fn ($r) => $r['stage'] === 'Purchase Invoices' && $r['status'] === 'Awaiting Approval');
        $this->assertEquals(1, $row['count'] ?? 0);
    }

    public function test_pending_transactions_counts_pending_payments(): void
    {
        $this->makePayment(['status' => 'pending_approval']);

        $result = $this->svc->getPendingTransactions($this->company->id);

        $payPending = collect($result)->firstWhere('status', 'Pending Authorization')['count'];
        $this->assertEquals(1, $payPending);
    }

    /* ═══════════════════════════════════════════════════════════
       getAlertCounts
       ═══════════════════════════════════════════════════════════ */

    public function test_alert_counts_zero(): void
    {
        $result = $this->svc->getAlertCounts($this->company->id);

        $this->assertEquals(0, $result['overdue_vendors']);
        $this->assertEquals(0, $result['due_within_7_days']);
        $this->assertEquals(0, $result['awaiting_authorization']);
    }

    public function test_alert_counts_overdue_vendors(): void
    {
        $this->makeBill([
            'vendor_id' => $this->vendorA->id,
            'amount' => 400,
            'due_date' => now()->subDays(3)->toDateString(),
            'status' => 'approved',
        ]);

        $result = $this->svc->getAlertCounts($this->company->id);

        $this->assertEquals(1, $result['overdue_vendors']);
    }

    public function test_alert_counts_due_within_7(): void
    {
        $this->makeBill([
            'amount' => 300,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'approved',
        ]);

        $result = $this->svc->getAlertCounts($this->company->id);

        $this->assertEquals(1, $result['due_within_7_days']);
    }

    public function test_alert_counts_awaiting_authorization(): void
    {
        $this->makePayment(['status' => 'pending_approval']);

        $result = $this->svc->getAlertCounts($this->company->id);

        $this->assertEquals(1, $result['awaiting_authorization']);
    }

    /* ═══════════════════════════════════════════════════════════
       getVendorBalances
       ═══════════════════════════════════════════════════════════ */

    public function test_vendor_balances_empty(): void
    {
        Vendor::query()->where('company_id', $this->company->id)->delete();
        $result = $this->svc->getVendorBalances($this->company->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_vendor_balances_waterfall_calculation(): void
    {
        $this->vendorB->delete();
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 1000, 'status' => 'approved']);
        $this->makePayment(['vendor_id' => $this->vendorA->id, 'amount' => 300, 'status' => 'posted']);
        $this->makeCredit(['vendor_id' => $this->vendorA->id, 'amount' => 100, 'status' => 'posted']);

        $result = $this->svc->getVendorBalances($this->company->id);

        $this->assertCount(1, $result);
        $bal = $result[0];
        $this->assertEqualsWithDelta(500, $bal['opening'], 0.01);
        $this->assertEqualsWithDelta(1000, $bal['purchases'], 0.01);
        $this->assertEqualsWithDelta(300, $bal['payments'], 0.01);
        $this->assertEqualsWithDelta(100, $bal['returns'], 0.01);
        $this->assertEqualsWithDelta(1100, $bal['closing'], 0.01);
    }

    public function test_vendor_balances_sorted_by_closing_desc(): void
    {
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 200, 'status' => 'approved']);
        $this->makeBill(['vendor_id' => $this->vendorB->id, 'amount' => 800, 'status' => 'approved']);

        $result = $this->svc->getVendorBalances($this->company->id);

        $this->assertGreaterThanOrEqual($result[1]['closing'], $result[0]['closing']);
    }

    public function test_vendor_balances_limit_5(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $v = Vendor::create([
                'company_id' => $this->company->id,
                'name' => "BalVendor $i",
                'is_active' => true,
            ]);
            $this->makeBill(['vendor_id' => $v->id, 'amount' => 100 * ($i + 1), 'status' => 'approved']);
        }

        $result = $this->svc->getVendorBalances($this->company->id, 5);

        $this->assertLessThanOrEqual(5, count($result));
    }

    public function test_vendor_balances_required_keys(): void
    {
        $this->makeBill(['vendor_id' => $this->vendorA->id, 'amount' => 500, 'status' => 'approved']);

        $result = $this->svc->getVendorBalances($this->company->id);

        $this->assertArrayHasKey('vendor_id', $result[0]);
        $this->assertArrayHasKey('vendor_name', $result[0]);
        $this->assertArrayHasKey('opening', $result[0]);
        $this->assertArrayHasKey('purchases', $result[0]);
        $this->assertArrayHasKey('payments', $result[0]);
        $this->assertArrayHasKey('returns', $result[0]);
        $this->assertArrayHasKey('closing', $result[0]);
    }

    /* ═══════════════════════════════════════════════════════════
       getVendorCount
       ═══════════════════════════════════════════════════════════ */

    public function test_vendor_count(): void
    {
        Vendor::create(['company_id' => $this->company->id, 'name' => 'Inactive', 'is_active' => false]);

        $result = $this->svc->getVendorCount($this->company->id);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['active']);
    }

    /* ═══════════════════════════════════════════════════════════
       severityDot (static)
       ═══════════════════════════════════════════════════════════ */

    public function test_severity_dot_today_is_red(): void
    {
        $dot = VendorCentreService::severityDot(now()->toDateString());
        $this->assertEquals('var(--red)', $dot);
    }

    public function test_severity_dot_tomorrow_is_orange(): void
    {
        $dot = VendorCentreService::severityDot(now()->addDay()->toDateString());
        $this->assertEquals('#d97706', $dot);
    }

    public function test_severity_dot_2_to_7_days_is_amber(): void
    {
        $dot = VendorCentreService::severityDot(now()->addDays(4)->toDateString());
        $this->assertEquals('var(--amber)', $dot);
    }

    public function test_severity_dot_8_plus_days_is_green(): void
    {
        $dot = VendorCentreService::severityDot(now()->addDays(10)->toDateString());
        $this->assertEquals('var(--green)', $dot);
    }

    public function test_severity_dot_boundary_6_days(): void
    {
        $dot6 = VendorCentreService::severityDot(now()->addDays(6)->toDateString());
        $this->assertEquals('var(--amber)', $dot6);

        $dot7 = VendorCentreService::severityDot(now()->addDays(7)->toDateString());
        $this->assertEquals('var(--green)', $dot7);
    }

    /* ═══════════════════════════════════════════════════════════
       compactAmount (static)
       ═══════════════════════════════════════════════════════════ */

    public function test_compact_amount_small(): void
    {
        $this->assertEquals('$999.00', VendorCentreService::compactAmount(999, '$'));
    }

    public function test_compact_amount_thousands(): void
    {
        $this->assertEquals('K5', VendorCentreService::compactAmount(5000, 'K'));
    }

    public function test_compact_amount_millions(): void
    {
        $this->assertEquals('K1.5M', VendorCentreService::compactAmount(1_500_000, 'K'));
    }

    public function test_compact_amount_negative(): void
    {
        $result = VendorCentreService::compactAmount(-3000, 'K');
        $this->assertStringStartsWith('-', $result);
        $this->assertStringContainsString('3', $result);
    }

    public function test_compact_amount_zero(): void
    {
        $this->assertStringContainsString('0.00', VendorCentreService::compactAmount(0, '$'));
    }

    /* ═══════════════════════════════════════════════════════════
       Cross-company isolation
       ═══════════════════════════════════════════════════════════ */

    public function test_metrics_are_company_scoped(): void
    {
        $otherCompany = Company::create([
            'company_code' => 'OTHER',
            'name' => 'Other Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $this->makeBill(['amount' => 5000, 'status' => 'approved']);
        Vendor::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Vendor',
            'is_active' => true,
        ]);

        $this->assertEquals(2, $this->svc->getVendorCount($this->company->id)['total']);
        $this->assertEquals(1, $this->svc->getVendorCount($otherCompany->id)['total']);
        $this->assertGreaterThan(0, $this->svc->getTotalPayables($this->company->id));
        $this->assertEquals(0, $this->svc->getTotalPayables($otherCompany->id));
    }
}
