<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Services\Accounting\BillService;
use App\Services\Accounting\ExpenseService;
use App\Services\Accounting\VendorCreditService;
use App\Services\Vendor\VendorCentreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorCentreTest extends TestCase
{
    use RefreshDatabase;

    protected VendorCentreService $service;
    protected Company $company;
    protected Vendor $vendor;
    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(VendorCentreService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TEST',
            'is_active' => true,
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '1150',
            'name' => 'Tax Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Test Vendor',
            'is_active' => true,
        ]);
    }

    public function test_vendor_summary_includes_bills_and_expenses(): void
    {
        $billService = app(BillService::class);
        $expenseService = app(ExpenseService::class);

        $expenseAccount = Account::where('company_id', $this->company->id)->where('code', '6100')->first();
        $cashAccount = Account::where('company_id', $this->company->id)->where('code', '1000')->first();

        $bill = $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-01',
            'due_date' => '2026-03-01',
            'lines' => [
                [
                    'description' => 'Service',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'expense_account_id' => $expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $billService->post($bill, $this->userId);

        $expense = $expenseService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-02-15',
            'bank_account_id' => $cashAccount->id,
            'lines' => [
                [
                    'description' => 'Utilities',
                    'quantity' => 1,
                    'unit_price' => 150,
                    'expense_account_id' => $expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $expenseService->post($expense, $this->userId);

        $summary = $this->service->getVendorSummary($this->company->id);
        $vendorSummary = $summary->firstWhere('id', $this->vendor->id);

        $this->assertEquals(500, $vendorSummary->total_bills);
        $this->assertEquals(150, $vendorSummary->expense_total);
    }

    public function test_vendor_timeline_merges_all_transaction_types(): void
    {
        $billService = app(BillService::class);

        $expenseAccount = Account::where('company_id', $this->company->id)->where('code', '6100')->first();

        $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-01',
            'due_date' => '2026-03-01',
            'lines' => [
                [
                    'description' => 'Service',
                    'quantity' => 1,
                    'unit_price' => 300,
                    'expense_account_id' => $expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $timeline = $this->service->getVendorTimeline($this->vendor, $this->company->id);

        $this->assertCount(1, $timeline);
        $this->assertEquals('bill', $timeline->first()['type']);
        $this->assertEquals(300, $timeline->first()['amount']);
    }

    public function test_vendor_stats_returns_correct_totals(): void
    {
        $stats = $this->service->getVendorStats($this->vendor, $this->company->id);

        $this->assertEquals(0, $stats['total_bills']);
        $this->assertEquals(0, $stats['total_paid']);
        $this->assertEquals(0, $stats['open_balance']);
        $this->assertEquals(0, $stats['total_expenses']);
        $this->assertEquals(0, $stats['credit_balance']);
        $this->assertEquals(0, $stats['bill_count']);
        $this->assertEquals(0, $stats['po_count']);
    }

    public function test_vendor_summary_excludes_inactive_vendors(): void
    {
        Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Vendor',
            'is_active' => false,
        ]);

        $summary = $this->service->getVendorSummary($this->company->id);

        $this->assertCount(1, $summary);
        $this->assertEquals('Test Vendor', $summary->first()->name);
    }

    public function test_vendor_timeline_sorted_by_date_desc(): void
    {
        $billService = app(BillService::class);
        $expenseAccount = Account::where('company_id', $this->company->id)->where('code', '6100')->first();
        $cashAccount = Account::where('company_id', $this->company->id)->where('code', '1000')->first();

        $billService->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'lines' => [
                [
                    'description' => 'Early bill',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'expense_account_id' => $expenseAccount->id,
                ],
            ],
        ], $this->userId);

        app(ExpenseService::class)->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-03-01',
            'bank_account_id' => $cashAccount->id,
            'lines' => [
                [
                    'description' => 'Late expense',
                    'quantity' => 1,
                    'unit_price' => 50,
                    'expense_account_id' => $expenseAccount->id,
                ],
            ],
        ], $this->userId);

        $timeline = $this->service->getVendorTimeline($this->vendor, $this->company->id);

        $this->assertEquals('expense', $timeline->first()['type']);
        $this->assertEquals('bill', $timeline->last()['type']);
    }
}
