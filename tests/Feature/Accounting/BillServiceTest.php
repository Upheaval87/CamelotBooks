<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\Vendor;
use App\Services\Accounting\BillService;
use App\Services\Accounting\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BillServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillService $service;

    protected Company $company;

    protected Account $apAccount;

    protected Account $expenseAccount;

    protected Account $taxReceivableAccount;

    protected Vendor $vendor;

    protected AccountingPeriod $period;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BillService::class);

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

        $this->apAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '6100',
            'name' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->taxReceivableAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1150',
            'name' => 'Tax Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies Co',
            'is_active' => true,
        ]);
    }

    protected function makeBillData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'internal_number' => null,
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ], $overrides);
    }

    public function test_posting_bill_creates_balanced_je_against_ap(): void
    {
        $bill = $this->service->create($this->makeBillData(), $this->userId);

        $this->assertEquals(Bill::STATUS_DRAFT, $bill->status);
        $this->assertEquals(2000.00, (float) $bill->amount);

        $bill = $this->service->post($bill, $this->userId);

        $this->assertEquals(Bill::STATUS_APPROVED, $bill->status);
        $this->assertNotNull($bill->journal_entry_id);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);
        $this->assertEquals('bill', $je->source_module);

        $lines = $je->lines()->get();
        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $apLine = $lines->firstWhere('account_id', $this->apAccount->id);
        $this->assertNotNull($apLine);
        $this->assertEquals(2000.00, (float) $apLine->credit);

        $expenseLine = $lines->firstWhere('account_id', $this->expenseAccount->id);
        $this->assertNotNull($expenseLine);
        $this->assertEquals(2000.00, (float) $expenseLine->debit);
    }

    public function test_posting_bill_with_tax_debits_tax_receivable(): void
    {
        $bill = $this->service->create($this->makeBillData([
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'tax_rate' => 10,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ]), $this->userId);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        $this->assertCount(3, $lines);

        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $taxLine = $lines->firstWhere('account_id', $this->taxReceivableAccount->id);
        $this->assertNotNull($taxLine);
        $this->assertEquals(200.00, (float) $taxLine->debit);

        $apLine = $lines->firstWhere('account_id', $this->apAccount->id);
        $this->assertEquals(2200.00, (float) $apLine->credit);
    }

    public function test_void_bill_reverses_je_and_restores_ap(): void
    {
        $bill = $this->service->create($this->makeBillData(), $this->userId);
        $bill = $this->service->post($bill, $this->userId);

        $this->assertEquals(2000.00, (float) $this->apAccount->fresh()->current_balance);

        $bill = $this->service->void($bill, 'Duplicate entry', $this->userId);

        $this->assertEquals(Bill::STATUS_VOID, $bill->status);
        $this->assertEquals('Duplicate entry', $bill->void_reason);

        $originalJe = JournalEntry::findOrFail($bill->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $originalJe->status);

        $this->assertEquals(0.00, (float) $this->apAccount->fresh()->current_balance);
    }

    public function test_draft_bill_cannot_be_voided(): void
    {
        $bill = $this->service->create($this->makeBillData(), $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Draft bills cannot be voided');

        $this->service->void($bill, 'test', $this->userId);
    }

    public function test_posted_bill_cannot_be_updated(): void
    {
        $bill = $this->service->create($this->makeBillData(), $this->userId);
        $bill = $this->service->post($bill, $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft bills can be updated');

        $this->service->update($bill, ['memo' => 'changed'], $this->userId);
    }

    public function test_approve_pending_bill_creates_je(): void
    {
        $bill = $this->service->create($this->makeBillData(), $this->userId);
        $bill->update(['status' => Bill::STATUS_PENDING_APPROVAL]);

        $bill = $this->service->approve($bill, $this->userId);

        $this->assertEquals(Bill::STATUS_APPROVED, $bill->status);
        $this->assertNotNull($bill->journal_entry_id);
        $this->assertNotNull($bill->approved_at);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);
    }

    public function test_bill_number_is_sequential(): void
    {
        $b1 = $this->service->create($this->makeBillData(), $this->userId);
        $b2 = $this->service->create($this->makeBillData(), $this->userId);

        $this->assertStringStartsWith('BILL-', $b1->bill_number);
        $this->assertStringStartsWith('BILL-', $b2->bill_number);

        $seq1 = (int) substr($b1->bill_number, strrpos($b1->bill_number, '-') + 1);
        $seq2 = (int) substr($b2->bill_number, strrpos($b2->bill_number, '-') + 1);

        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_vendor_payment_split_across_two_bills(): void
    {
        $bill1 = $this->service->create($this->makeBillData([
            'lines' => [
                [
                    'description' => 'Line 1',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ]), $this->userId);
        $bill1 = $this->service->post($bill1, $this->userId);

        $bill2 = $this->service->create($this->makeBillData([
            'lines' => [
                [
                    'description' => 'Line 2',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ]), $this->userId);
        $bill2 = $this->service->post($bill2, $this->userId);

        $bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $paymentService = app(PaymentService::class);

        $payment = $paymentService->createVendorPayment([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'payment_date' => '2026-02-20',
            'amount' => 1000,
            'bank_account_id' => $bankAccount->id,
            'allocations' => [
                ['bill_id' => $bill1->id, 'amount' => 500],
                ['bill_id' => $bill2->id, 'amount' => 500],
            ],
        ], $this->userId);

        $payment = $paymentService->postVendorPayment($payment, $this->userId);

        $bill1->refresh();
        $bill2->refresh();

        $this->assertEquals(500.00, (float) $bill1->amount_paid);
        $this->assertEquals(Bill::STATUS_PAID, $bill1->status);
        $this->assertEquals(500.00, (float) $bill2->amount_paid);
        $this->assertEquals(Bill::STATUS_PAID, $bill2->status);
    }

    public function test_cost_center_propagates_to_je_lines_on_bill_post(): void
    {
        $costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'code' => 'CC-OPS',
            'name' => 'Operations',
            'is_active' => true,
        ]);

        $bill = $this->service->create($this->makeBillData([
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'expense_account_id' => $this->expenseAccount->id,
                    'cost_center_id' => $costCenter->id,
                ],
            ],
        ]), $this->userId);

        $line = $bill->lines()->first();
        $this->assertEquals($costCenter->id, $line->cost_center_id);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        foreach ($lines as $jeLine) {
            $this->assertEquals($costCenter->id, $jeLine->cost_center_id);
        }
    }

    public function test_cost_center_null_on_je_lines_when_not_set(): void
    {
        $bill = $this->service->create($this->makeBillData(), $this->userId);
        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        foreach ($lines as $jeLine) {
            $this->assertNull($jeLine->cost_center_id);
        }
    }

    public function test_create_persists_supplier_info_and_charges(): void
    {
        $branch = \App\Models\Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Headquarters',
            'code' => 'HQ',
        ]);

        $bill = $this->service->create($this->makeBillData([
            'branch_id' => $branch->id,
            'currency' => 'USD',
            'exchange_rate' => 1.25,
            'po_number' => 'PO-0001',
            'grn_reference' => 'GRN-0001',
            'supplier_notes' => 'Ship to warehouse B.',
            'payment_instructions' => 'Bank transfer to vendor account.',
            'freight_charges' => 100,
            'insurance_charges' => 50,
            'customs_charges' => 25,
            'other_charges' => 10,
        ]), $this->userId);

        $bill->refresh();

        $this->assertEquals($branch->id, $bill->branch_id);
        $this->assertEquals('USD', $bill->currency);
        $this->assertEquals('1.25', (string) $bill->exchange_rate);
        $this->assertEquals('PO-0001', $bill->po_number);
        $this->assertEquals('GRN-0001', $bill->grn_reference);
        $this->assertEquals('Ship to warehouse B.', $bill->supplier_notes);
        $this->assertEquals('Bank transfer to vendor account.', $bill->payment_instructions);
        $this->assertEquals(185.00, $bill->totalCharges());
        $this->assertEquals(2185.00, (float) $bill->amount);
    }

    public function test_update_updates_charges_and_refs(): void
    {
        $bill = $this->service->create($this->makeBillData(['freight_charges' => 100]), $this->userId);
        $this->assertEquals(2100.00, (float) $bill->amount);

        $bill = $this->service->update($bill, [
            'freight_charges' => 200,
            'customs_charges' => 50,
            'po_number' => 'PO-0002',
            'grn_reference' => 'GRN-0002',
        ], $this->userId);

        $bill->refresh();

        $this->assertEquals(200.00, (float) $bill->freight_charges);
        $this->assertEquals(50.00, (float) $bill->customs_charges);
        $this->assertEquals('PO-0002', $bill->po_number);
        $this->assertEquals('GRN-0002', $bill->grn_reference);
        $this->assertEquals(2250.00, (float) $bill->amount);
    }

    public function test_posting_bill_with_charges_creates_balanced_je(): void
    {
        $bill = $this->service->create($this->makeBillData([
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
            'freight_charges' => 100,
            'customs_charges' => 50,
        ]), $this->userId);

        $this->assertEquals(2150.00, (float) $bill->amount);

        $bill = $this->service->post($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $apCredit = $lines->where('account_id', $this->apAccount->id)->sum('credit');
        $this->assertEquals(2150.00, (float) $apCredit);

        $expenseLines = $lines->where('account_id', $this->expenseAccount->id);
        $this->assertEquals(2150.00, round($expenseLines->sum('debit'), 2));
    }

    public function test_approve_pending_bill_with_charges_and_tax_creates_balanced_je(): void
    {
        $bill = $this->service->create($this->makeBillData([
            'lines' => [
                [
                    'description' => 'Monthly rent',
                    'quantity' => 1,
                    'unit_price' => 2000,
                    'tax_rate' => 10,
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
            'freight_charges' => 150,
        ]), $this->userId);

        $bill->update(['status' => Bill::STATUS_PENDING_APPROVAL]);
        $bill = $this->service->approve($bill, $this->userId);

        $je = JournalEntry::findOrFail($bill->journal_entry_id);
        $lines = $je->lines()->get();

        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        // amount = 2200 (line total incl. tax) + 150 freight = 2350
        $apCredit = $lines->where('account_id', $this->apAccount->id)->sum('credit');
        $this->assertEquals(2350.00, (float) $apCredit);
    }
}
