<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceAllocation;
use App\Models\PosPaymentMethod;
use App\Models\SalesReceipt;
use App\Services\Accounting\SalesReceiptService;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Receipt from Invoice" — additive settlement of invoices via sales receipts.
 * Standalone receipt posting is untouched (verified in standalone tests).
 */
class SalesReceiptSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected int $userId;
    protected Account $arAccount;
    protected Account $bank;
    protected Customer $customer;
    protected PosPaymentMethod $method;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = \App\Models\User::factory()->create()->id;

        $this->company = Company::create([
            'name' => 'Settlement Co',
            'company_code' => 'SETTLE',
            'base_currency' => 'MWK',
            'is_active' => true,
        ]);

        $this->arAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->bank = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash & Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank' => true,
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q3',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'status' => 'open',
        ]);

        app(NumberingSequenceService::class)->seedDefaults($this->company->id);

        $this->method = PosPaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'type' => 'cash',
            'clearing_account_id' => $this->bank->id,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Widget Co',
        ]);
    }

    protected function makeInvoice(string $number, float $amount, float $paid, string $status): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => $number,
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'status' => $status,
            'amount' => $amount,
            'amount_paid' => $paid,
            'settled' => 0,
            'created_by' => $this->userId,
        ]);
    }

    /**
     * An acting user with company access, so the tenant.bind / company.context
     * middleware and company-scoping pass for HTTP routes.
     */
    protected function actingAdmin(): \App\Models\User
    {
        $user = \App\Models\User::factory()->create();
        $user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $user->assignRole('company_admin');
        $this->actingAs($user);
        session(['current_company_id' => $this->company->id]);

        return $user;
    }

    protected function service(): SalesReceiptService
    {
        return app(SalesReceiptService::class);
    }

    protected function newSettlementReceipt(Invoice $invoice, float $payment, ?int $bankAccountId = null): SalesReceipt
    {
        $payments = [[
            'payment_method_id' => $this->method->id,
            'amount' => $payment,
        ]];
        if ($bankAccountId) {
            $payments[0]['bank_account_id'] = $bankAccountId;
        }

        return $this->service()->create([
            'company_id' => $this->company->id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'receipt_date' => '2026-08-15',
            'currency' => 'MWK',
            'lines' => [],
            'payments' => $payments,
        ], $this->userId);
    }

    public function test_full_settlement_posts_ar_journal_and_marks_invoice_paid(): void
    {
        $invoice = $this->makeInvoice('INV-100', 500.00, 0, Invoice::STATUS_SENT);

        $receipt = $this->newSettlementReceipt($invoice, 500.00);
        $this->service()->post($receipt, $this->userId);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertEquals(500.00, (float) $invoice->amount_paid);
        $this->assertEquals(500.00, (float) $invoice->settled);

        $this->assertDatabaseHas('invoice_allocations', [
            'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id,
            'applied_amount' => 500.00,
        ]);

        $receipt->refresh(['journalEntry']);
        $this->assertSame(SalesReceipt::STATUS_POSTED, $receipt->status);
        $this->assertNotNull($receipt->journal_entry_id);

        // Journal: Dr bank 500 · Cr AR 500.
        $lines = $receipt->journalEntry->lines;
        $this->assertEquals(500.00, (float) $lines->where('account_id', $this->bank->id)->sum('debit'));
        $this->assertEquals(500.00, (float) $lines->where('account_id', $this->arAccount->id)->sum('credit'));
        $this->assertEquals(500.00, (float) $lines->sum('debit'));
        $this->assertEquals(500.00, (float) $lines->sum('credit'));
    }

    public function test_partial_settlement_marks_invoice_partially_paid(): void
    {
        $invoice = $this->makeInvoice('INV-101', 500.00, 100.00, Invoice::STATUS_PARTIALLY_PAID);

        $receipt = $this->newSettlementReceipt($invoice, 250.00);
        $this->service()->post($receipt, $this->userId);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PARTIALLY_PAID, $invoice->status);
        $this->assertEquals(350.00, (float) $invoice->amount_paid);
        $this->assertEquals(250.00, (float) $invoice->settled);

        $this->assertDatabaseHas('invoice_allocations', [
            'invoice_id' => $invoice->id,
            'applied_amount' => 250.00,
        ]);
    }

    public function test_overpayment_is_capped_at_invoice_balance_and_credited_when_policy_allows(): void
    {
        $invoice = $this->makeInvoice('INV-102', 300.00, 0, Invoice::STATUS_SENT);

        $customerCredit = Account::create([
            'company_id' => $this->company->id,
            'code' => '2200',
            'name' => 'Customer Credit Liability',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        config(['sales_receipts.overpayment_policy' => 'customer_credit']);

        // Pay 500 on a 300 invoice — 300 applied, 200 to customer credit.
        $receipt = $this->newSettlementReceipt($invoice, 500.00);
        $this->service()->post($receipt, $this->userId);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertEquals(300.00, (float) $invoice->amount_paid);
        $this->assertEquals(300.00, (float) $invoice->settled);

        $this->assertDatabaseHas('invoice_allocations', [
            'invoice_id' => $invoice->id,
            'applied_amount' => 300.00,
        ]);

        // Journal stays balanced: Dr bank 500 · Cr AR 300 · Cr customer credit 200.
        $receipt->refresh(['journalEntry']);
        $lines = $receipt->journalEntry->lines;
        $this->assertEquals(500.00, (float) $lines->sum('debit'));
        $this->assertEquals(500.00, (float) $lines->sum('credit'));
        $this->assertEquals(300.00, (float) $lines->where('account_id', $this->arAccount->id)->sum('credit'));
        $this->assertEquals(200.00, (float) $lines->where('account_id', $customerCredit->id)->sum('credit'));
    }

    public function test_overpayment_cap_without_credit_account_rejects_posting(): void
    {
        $invoice = $this->makeInvoice('INV-205', 300.00, 0, Invoice::STATUS_SENT);
        config(['sales_receipts.overpayment_policy' => 'cap']);

        // default policy = cap, no credit account → posting is out of balance and must fail safely.
        $receipt = $this->newSettlementReceipt($invoice, 500.00);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('out of balance');
        $this->service()->post($receipt, $this->userId);
    }

    public function test_void_reverses_allocations_and_restores_invoice(): void
    {
        $invoice = $this->makeInvoice('INV-103', 500.00, 0, Invoice::STATUS_SENT);

        $receipt = $this->newSettlementReceipt($invoice, 500.00);
        $this->service()->post($receipt, $this->userId);

        $this->service()->void($receipt->fresh(), 'Posted in error', $this->userId);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertEquals(0, (float) $invoice->amount_paid);
        $this->assertEquals(0, (float) $invoice->settled);

        $this->assertDatabaseMissing('invoice_allocations', [
            'receipt_id' => $receipt->id,
        ]);
        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receipt->id,
            'status' => SalesReceipt::STATUS_VOIDED,
        ]);
    }

    public function test_standalone_receipt_is_unchanged_and_posts_revenue(): void
    {
        $income = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'is_active' => true,
        ]);

        $receipt = $this->service()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'receipt_date' => '2026-08-15',
            'currency' => 'MWK',
            'lines' => [[
                'product_id' => null,
                'description' => 'Consulting',
                'quantity' => 1,
                'unit_price' => 100.00,
                'discount' => 0,
                'tax_rate' => 0,
                'income_account_id' => $income->id,
            ]],
            'payments' => [[
                'payment_method_id' => $this->method->id,
                'amount' => 100.00,
            ]],
        ], $this->userId);

        $this->assertNull($receipt->invoice_id);
        $this->service()->post($receipt, $this->userId);

        $receipt->refresh(['journalEntry']);
        $this->assertEquals(100.00, (float) $receipt->journalEntry->lines->sum('debit'));
        $this->assertEquals(100.00, (float) $receipt->journalEntry->lines->where('account_id', $income->id)->sum('credit'));
        // No AR involvement for a standalone receipt.
        $this->assertEquals(0, (float) $receipt->journalEntry->lines->where('account_id', $this->arAccount->id)->sum('credit'));
    }

    public function test_locate_invoices_returns_only_outstanding_balances(): void
    {
        $pending = $this->makeInvoice('INV-200', 400.00, 0, Invoice::STATUS_SENT);
        $partial = $this->makeInvoice('INV-201', 300.00, 100.00, Invoice::STATUS_PARTIALLY_PAID);
        $overdue = $this->makeInvoice('INV-202', 200.00, 0, Invoice::STATUS_OVERDUE);
        $paid = $this->makeInvoice('INV-203', 500.00, 500.00, Invoice::STATUS_PAID);
        $draft = $this->makeInvoice('INV-204', 100.00, 0, Invoice::STATUS_DRAFT);

        $this->actingAdmin();

        $res = $this->getJson(route('accounting.sales-receipts.locate-invoices'))            ->assertOk()
            ->assertJsonStructure(['invoices' => [[
                'id', 'invoice_number', 'customer_name', 'amount', 'amount_paid', 'balance', 'status',
            ]]]);

        $ids = collect($res->json('invoices'))->pluck('id');
        $this->assertTrue($ids->contains($pending->id));
        $this->assertTrue($ids->contains($partial->id));
        $this->assertTrue($ids->contains($overdue->id));
        $this->assertFalse($ids->contains($paid->id));
        $this->assertFalse($ids->contains($draft->id));

        // Every returned invoice must have balance > 0.
        foreach ($res->json('invoices') as $inv) {
            $this->assertGreaterThan(0, $inv['balance']);
        }
    }

    public function test_http_create_executes_settlement_without_required_lines(): void
    {
        $this->actingAdmin();

        $invoice = $this->makeInvoice('INV-300', 250.00, 0, Invoice::STATUS_SENT);

        $res = $this->post(route('accounting.sales-receipts.store'), [
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'receipt_date' => '2026-08-20',
            'currency' => 'MWK',
            'lines' => [],
            'payments' => [[
                'payment_method_id' => $this->method->id,
                'amount' => 250.00,
            ]],
        ]);

        $this->assertTrue($res->isRedirect());

        $this->assertDatabaseHas('sales_receipts', [
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'status' => SalesReceipt::STATUS_DRAFT,
        ]);

        $receipt = SalesReceipt::where('invoice_id', $invoice->id)->first();
        $this->assertEquals(250.00, (float) $receipt->total);
        $this->assertEquals(0, $receipt->lines()->count());
        $this->assertEquals(1, $receipt->payments()->count());
    }

    public function test_cross_company_invoice_forge_is_rejected(): void
    {
        $this->actingAdmin();

        $other = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OTH2',
            'is_active' => true,
        ]);
        $otherCustomer = Customer::create(['company_id' => $other->id, 'name' => 'Other Cust']);
        $foreign = Invoice::create([
            'company_id' => $other->id,
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'INV-X1',
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'status' => Invoice::STATUS_SENT,
            'amount' => 100,
            'amount_paid' => 0,
            'created_by' => $this->userId,
        ]);

        $res = $this->post(route('accounting.sales-receipts.store'), [
            'invoice_id' => $foreign->id,
            'receipt_date' => '2026-08-20',
            'lines' => [],
            'payments' => [[
                'payment_method_id' => $this->method->id,
                'amount' => 100.00,
            ]],
        ]);

        $res->assertStatus(403);
        $this->assertDatabaseCount('sales_receipts', 0);
    }
}
