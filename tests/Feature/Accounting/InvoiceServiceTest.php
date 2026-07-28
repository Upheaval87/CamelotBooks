<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Services\Accounting\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $service;

    protected Company $company;

    protected Account $arAccount;

    protected Account $incomeAccount;

    protected Account $taxPayableAccount;

    protected Customer $customer;

    protected AccountingPeriod $period;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvoiceService::class);

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

        $this->arAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        $this->taxPayableAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '2300',
            'name' => 'Sales Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        Account::create(['company_id' => $this->company->id, 'code' => '9999', 'name' => 'Rounding Differences', 'type' => 'expense', 'sub_type' => 'non_operating_expense', 'is_active' => true]);

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'accounts_receivable' => '1100',
            'default_revenue' => '4000',
            'tax_payable' => '2300',
            'rounding' => '9999',
        ];
        foreach ($mappingData as $key => $code) {
            if (isset($accounts[$code])) {
                \App\Models\DefaultAccountMapping::setMapping(
                    $this->company->id, $key, $accounts[$code]->id
                );
            }
        }

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corp',
            'is_active' => true,
        ]);
    }

    protected function makeInvoiceData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'lines' => [
                [
                    'description' => 'Consulting services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $overrides);
    }

    public function test_posting_invoice_creates_balanced_je_against_ar(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData(), $this->userId);

        $this->assertEquals(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertEquals(1000.00, (float) $invoice->amount);

        $invoice = $this->service->post($invoice, $this->userId);

        $this->assertEquals(Invoice::STATUS_SENT, $invoice->status);
        $this->assertNotNull($invoice->journal_entry_id);

        $je = JournalEntry::findOrFail($invoice->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);
        $this->assertEquals('invoice', $je->source_module);

        $lines = $je->lines()->get();
        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));
        $this->assertEquals(1000.00, $debitTotal);

        $arLine = $lines->firstWhere('account_id', $this->arAccount->id);
        $this->assertNotNull($arLine);
        $this->assertEquals(1000.00, (float) $arLine->debit);

        $incomeLine = $lines->firstWhere('account_id', $this->incomeAccount->id);
        $this->assertNotNull($incomeLine);
        $this->assertEquals(1000.00, (float) $incomeLine->credit);
    }

    public function test_posting_invoice_with_tax_creates_three_je_lines(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData([
            'lines' => [
                [
                    'description' => 'Consulting services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'tax_rate' => 10,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ]), $this->userId);

        $invoice = $this->service->post($invoice, $this->userId);

        $je = JournalEntry::findOrFail($invoice->journal_entry_id);
        $lines = $je->lines()->get();

        $this->assertCount(3, $lines);

        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $arLine = $lines->firstWhere('account_id', $this->arAccount->id);
        $this->assertEquals(1100.00, (float) $arLine->debit);

        $taxLine = $lines->firstWhere('account_id', $this->taxPayableAccount->id);
        $this->assertNotNull($taxLine);
        $this->assertEquals(100.00, (float) $taxLine->credit);
    }

    public function test_void_invoice_reverses_je_and_restores_ar(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData(), $this->userId);
        $invoice = $this->service->post($invoice, $this->userId);

        $this->assertEquals(1000.00, (float) $this->arAccount->fresh()->current_balance);

        $invoice = $this->service->void($invoice, 'Customer dispute', $this->userId);

        $this->assertEquals(Invoice::STATUS_VOID, $invoice->status);
        $this->assertEquals('Customer dispute', $invoice->void_reason);
        $this->assertNotNull($invoice->voided_at);

        $originalJe = JournalEntry::findOrFail($invoice->journal_entry_id);
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $originalJe->status);

        $this->assertEquals(0.00, (float) $this->arAccount->fresh()->current_balance);
    }

    public function test_draft_invoice_cannot_be_voided(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData(), $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Draft invoices cannot be voided');

        $this->service->void($invoice, 'test', $this->userId);
    }

    public function test_posted_invoice_cannot_be_updated(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData(), $this->userId);
        $invoice = $this->service->post($invoice, $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft invoices can be updated');

        $this->service->update($invoice, ['memo' => 'changed'], $this->userId);
    }

    public function test_payment_split_across_two_invoices_updates_both(): void
    {
        $invoice1 = $this->service->create($this->makeInvoiceData([
            'lines' => [
                [
                    'description' => 'Line 1',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ]), $this->userId);
        $invoice1 = $this->service->post($invoice1, $this->userId);

        $invoice2 = $this->service->create($this->makeInvoiceData([
            'lines' => [
                [
                    'description' => 'Line 2',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ]), $this->userId);
        $invoice2 = $this->service->post($invoice2, $this->userId);

        $bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $paymentService = app(\App\Services\Accounting\PaymentService::class);

        $payment = $paymentService->createCustomerPayment([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_date' => '2026-02-20',
            'amount' => 1000,
            'bank_account_id' => $bankAccount->id,
            'allocations' => [
                ['invoice_id' => $invoice1->id, 'amount' => 500],
                ['invoice_id' => $invoice2->id, 'amount' => 500],
            ],
        ], $this->userId);

        $payment = $paymentService->postCustomerPayment($payment, $this->userId);

        $invoice1->refresh();
        $invoice2->refresh();

        $this->assertEquals(500.00, (float) $invoice1->amount_paid);
        $this->assertEquals(Invoice::STATUS_PAID, $invoice1->status);
        $this->assertEquals(500.00, (float) $invoice2->amount_paid);
        $this->assertEquals(Invoice::STATUS_PAID, $invoice2->status);
    }

    public function test_partial_payment_marks_invoice_partially_paid(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData(), $this->userId);
        $invoice = $this->service->post($invoice, $this->userId);

        $bankAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $paymentService = app(\App\Services\Accounting\PaymentService::class);

        $payment = $paymentService->createCustomerPayment([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_date' => '2026-02-20',
            'amount' => 400,
            'bank_account_id' => $bankAccount->id,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 400],
            ],
        ], $this->userId);

        $payment = $paymentService->postCustomerPayment($payment, $this->userId);

        $invoice->refresh();

        $this->assertEquals(400.00, (float) $invoice->amount_paid);
        $this->assertEquals(Invoice::STATUS_PARTIALLY_PAID, $invoice->status);
        $this->assertEquals(600.00, (float) $invoice->balance_due);
    }

    public function test_cannot_post_invoice_with_no_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one invoice line is required');

        $this->service->create($this->makeInvoiceData(['lines' => []]), $this->userId);
    }

    public function test_invoice_number_is_sequential(): void
    {
        $inv1 = $this->service->create($this->makeInvoiceData(), $this->userId);
        $inv2 = $this->service->create($this->makeInvoiceData(), $this->userId);

        $this->assertStringStartsWith('INV-', $inv1->invoice_number);
        $this->assertStringStartsWith('INV-', $inv2->invoice_number);

        $seq1 = (int) substr($inv1->invoice_number, strrpos($inv1->invoice_number, '-') + 1);
        $seq2 = (int) substr($inv2->invoice_number, strrpos($inv2->invoice_number, '-') + 1);

        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_cost_center_propagates_to_je_lines_on_invoice_post(): void
    {
        $costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'code' => 'CC-SALES',
            'name' => 'Sales Division',
            'is_active' => true,
        ]);

        $invoice = $this->service->create($this->makeInvoiceData([
            'lines' => [
                [
                    'description' => 'Consulting services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'income_account_id' => $this->incomeAccount->id,
                    'cost_center_id' => $costCenter->id,
                ],
            ],
        ]), $this->userId);

        $line = $invoice->lines()->first();
        $this->assertEquals($costCenter->id, $line->cost_center_id);

        $invoice = $this->service->post($invoice, $this->userId);

        $je = JournalEntry::findOrFail($invoice->journal_entry_id);
        $lines = $je->lines()->get();

        foreach ($lines as $jeLine) {
            $this->assertEquals($costCenter->id, $jeLine->cost_center_id);
        }
    }

    public function test_cost_center_null_on_je_lines_when_not_set(): void
    {
        $invoice = $this->service->create($this->makeInvoiceData(), $this->userId);
        $invoice = $this->service->post($invoice, $this->userId);

        $je = JournalEntry::findOrFail($invoice->journal_entry_id);
        $lines = $je->lines()->get();

        foreach ($lines as $jeLine) {
            $this->assertNull($jeLine->cost_center_id);
        }
    }
}
