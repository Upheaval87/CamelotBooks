<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Reporting\IncomeStatementService;
use App\Services\Reporting\BalanceSheetService;
use App\Services\Reporting\CashFlowStatementService;
use App\Services\Reporting\AgingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementsTest extends TestCase
{
    use RefreshDatabase;

    protected JournalPostingEngine $engine;

    protected Company $company;

    protected Account $cashAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected Account $retainedEarnings;

    protected Account $arAccount;

    protected Account $depreciationExpense;

    protected Account $accumulatedDepreciation;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(JournalPostingEngine::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Report Test Co',
            'company_code' => 'RPT',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank_account' => true,
        ]);

        $this->arAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'cash_flow_section' => 'operating',
        ]);

        $this->revenueAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->depreciationExpense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Depreciation Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
            'is_non_cash' => true,
        ]);

        $this->accumulatedDepreciation = Account::create([
            'company_id' => $this->company->id,
            'code' => '1500',
            'name' => 'Accumulated Depreciation',
            'type' => 'asset',
            'sub_type' => 'non_current_asset',
            'is_active' => true,
            'is_non_cash' => false,
        ]);

        $this->retainedEarnings = Account::create([
            'company_id' => $this->company->id,
            'code' => '3100',
            'name' => 'Retained Earnings',
            'type' => 'equity',
            'sub_type' => 'equity',
            'is_active' => true,
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);
    }

    public function test_income_statement_matches_ledger_totals(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-01-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-10',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 3000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 3000],
            ],
        ]);

        $service = app(IncomeStatementService::class);
        $result = $service->generate($this->company->id, null, '2026-01-01', '2026-03-31');

        $this->assertEquals(10000.0, $result['total_income']);
        $this->assertEquals(3000.0, $result['total_expenses']);
        $this->assertEquals(7000.0, $result['net_income']);
    }

    public function test_balance_sheet_equation_holds(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-01-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-10',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 3000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 3000],
            ],
        ]);

        $service = app(BalanceSheetService::class);
        $result = $service->generate($this->company->id, null, '2026-03-31');

        $this->assertTrue($result['balanced']);
        $this->assertEquals($result['total_assets'], $result['total_liabilities'] + $result['total_equity']);
    }

    public function test_balance_sheet_current_year_earnings(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 5000],
            ],
        ]);

        $service = app(BalanceSheetService::class);
        $result = $service->generate($this->company->id, null, '2026-03-31');

        $this->assertEquals(5000.0, $result['current_year_earnings']);
        $this->assertEquals(5000.0, $result['total_assets']);
        $this->assertEquals(5000.0, $result['total_equity']);
    }

    public function test_cash_flow_ending_cash_matches_bank_balances(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-01-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-10',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 3000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 3000],
            ],
        ]);

        $service = app(CashFlowStatementService::class);
        $result = $service->generate($this->company->id, null, '2026-01-01', '2026-03-31');

        $this->assertNull($result['mismatch']);
        $this->assertEquals($result['ending_cash'], $result['actual_ending_cash']);
        $this->assertEquals(7000.0, $result['ending_cash']);
    }

    public function test_cash_flow_non_cash_expenses_add_back(): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-02-15',
            'lines' => [
                ['account_id' => $this->depreciationExpense->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->accumulatedDepreciation->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $service = app(CashFlowStatementService::class);
        $result = $service->generate($this->company->id, null, '2026-01-01', '2026-03-31');

        $this->assertCount(1, $result['non_cash_expenses']['items']);
        $this->assertEquals(1000.0, $result['non_cash_expenses']['total']);
        $this->assertEquals('Depreciation Expense', $result['non_cash_expenses']['items'][0]['account']->name);
    }

    public function test_aging_ar_totals_match_outstanding_invoices(): void
    {
        $customer = \App\Models\Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'is_active' => true,
        ]);

        $invoice = \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_by' => $this->userId,
            'invoice_number' => 'INV-2026-0001',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-02-01',
            'status' => 'posted',
            'amount' => 5000,
            'amount_paid' => 0,
        ]);

        $service = app(AgingReportService::class);
        $result = $service->arAging($this->company->id, null, '2026-03-15');

        $this->assertEquals(5000.0, $result['totals']['total']);
        $this->assertNotEmpty($result['customers']);
    }

    public function test_aging_ap_totals_match_outstanding_bills(): void
    {
        $vendor = \App\Models\Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Test Vendor',
            'is_active' => true,
        ]);

        $bill = \App\Models\Bill::create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'created_by' => $this->userId,
            'bill_number' => 'BILL-2026-0001',
            'bill_date' => '2026-01-01',
            'due_date' => '2026-02-01',
            'status' => 'posted',
            'amount' => 3000,
            'amount_paid' => 0,
        ]);

        $service = app(AgingReportService::class);
        $result = $service->apAging($this->company->id, null, '2026-03-15');

        $this->assertEquals(3000.0, $result['totals']['total']);
        $this->assertNotEmpty($result['vendors']);
    }

    public function test_income_statement_excludes_draft_entries(): void
    {
        $this->engine->postAsDraft([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-01-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 5000],
            ],
        ]);

        $service = app(IncomeStatementService::class);
        $result = $service->generate($this->company->id, null, '2026-01-01', '2026-03-31');

        $this->assertEquals(0.0, $result['total_income']);
        $this->assertEquals(0.0, $result['net_income']);
    }

    public function test_balance_sheet_excludes_reversed_entries(): void
    {
        $entry = $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->userId,
            'date' => '2026-01-15',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $this->engine->reverse($entry->id, $this->userId, '2026-02-01');

        $service = app(BalanceSheetService::class);
        $result = $service->generate($this->company->id, null, '2026-03-31');

        $this->assertEquals(0.0, $result['total_assets']);
        $this->assertEquals(0.0, $result['total_equity']);
    }

    public function test_balance_sheet_with_opening_balance(): void
    {
        $this->cashAccount->update(['opening_balance' => 5000]);
        $this->retainedEarnings->update(['opening_balance' => 5000]);

        $service = app(BalanceSheetService::class);
        $result = $service->generate($this->company->id, null, '2026-01-01');

        $this->assertEquals(5000.0, $result['total_assets']);
        $this->assertTrue($result['balanced']);
    }

    public function test_aging_buckets_distributes_correctly(): void
    {
        $customer = \App\Models\Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Bucket Customer',
            'is_active' => true,
        ]);

        \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_by' => $this->userId,
            'invoice_number' => 'INV-2026-0010',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-03-10',
            'status' => 'posted',
            'amount' => 1000,
            'amount_paid' => 0,
        ]);

        \App\Models\Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_by' => $this->userId,
            'invoice_number' => 'INV-2026-0011',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-02-01',
            'status' => 'posted',
            'amount' => 2000,
            'amount_paid' => 0,
        ]);

        $service = app(AgingReportService::class);
        $result = $service->arAging($this->company->id, null, '2026-03-15');

        $this->assertCount(1, $result['customers']);
        $this->assertEquals(3000.0, $result['totals']['total']);
    }
}