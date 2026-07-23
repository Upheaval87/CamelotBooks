<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\ForeignCurrencyService;
use App\Services\Accounting\InvoiceService;
use App\Services\Accounting\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private int $userId;
    private AccountingPeriod $period;
    private Account $arAccount;
    private Account $incomeAccount;
    private Account $cashAccount;
    private Account $fxGainLossAccount;
    private Account $unrealizedFxAccount;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->userId = $this->user->id;

        $this->company = Company::create([
            'name' => 'FX Test Co',
            'company_code' => 'FXTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        session(['current_company_id' => $this->company->id]);

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
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_bank_account' => true,
            'is_active' => true,
        ]);

        $this->fxGainLossAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '7200',
            'name' => 'Realized FX Gain/Loss',
            'type' => 'expense',
            'sub_type' => 'other_expense',
            'is_active' => true,
        ]);

        $this->unrealizedFxAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '7300',
            'name' => 'Unrealized FX Gain/Loss',
            'type' => 'expense',
            'sub_type' => 'other_expense',
            'is_active' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'code' => '2300',
            'name' => 'Tax Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
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

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Foreign Client',
            'is_active' => true,
        ]);
    }

    public function test_exchange_rate_crud(): void
    {
        $rate = ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.10000000,
            'effective_date' => '2026-01-15',
        ]);

        $this->assertDatabaseHas('exchange_rates', [
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
        ]);

        $fetched = ExchangeRate::getRate($this->company->id, 'EUR', 'USD', '2026-01-20');
        $this->assertEquals(1.1, $fetched);

        $rate->delete();
        $this->assertDatabaseMissing('exchange_rates', ['id' => $rate->id]);
    }

    public function test_exchange_rate_isolation_between_companies(): void
    {
        $company2 = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OTHR',
            'base_currency' => 'GBP',
            'is_active' => true,
        ]);

        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.10,
            'effective_date' => '2026-01-15',
        ]);

        ExchangeRate::create([
            'company_id' => $company2->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.20,
            'effective_date' => '2026-01-15',
        ]);

        $rate1 = ExchangeRate::getRate($this->company->id, 'EUR', 'USD', '2026-01-20');
        $rate2 = ExchangeRate::getRate($company2->id, 'EUR', 'USD', '2026-01-20');

        $this->assertEquals(1.1, $rate1);
        $this->assertEquals(1.2, $rate2);
    }

    public function test_same_currency_returns_rate_one(): void
    {
        $rate = ExchangeRate::getRate($this->company->id, 'USD', 'USD', '2026-01-15');
        $this->assertEquals(1.0, $rate);
    }

    public function test_get_rate_returns_latest_before_date(): void
    {
        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.08,
            'effective_date' => '2026-01-01',
        ]);

        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.12,
            'effective_date' => '2026-03-01',
        ]);

        $this->assertEquals(1.08, ExchangeRate::getRate($this->company->id, 'EUR', 'USD', '2026-01-15'));
        $this->assertEquals(1.12, ExchangeRate::getRate($this->company->id, 'EUR', 'USD', '2026-06-01'));
    }

    public function test_foreign_currency_invoice_conversion(): void
    {
        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.10,
            'effective_date' => '2026-01-15',
        ]);

        $invoiceService = app(InvoiceService::class);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'currency' => 'EUR',
            'lines' => [
                [
                    'description' => 'Services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $this->assertEquals('EUR', $invoice->currency);
        $this->assertEquals(1000.00, (float) $invoice->amount);

        $invoice = $invoiceService->post($invoice, $this->userId);

        $this->assertEquals(1.10, (float) $invoice->exchange_rate);
        $this->assertEquals(1100.00, (float) $invoice->base_amount);

        $je = JournalEntry::findOrFail($invoice->journal_entry_id);
        $lines = $je->lines()->get();

        $this->assertCount(2, $lines);

        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $arLine = $lines->firstWhere('account_id', $this->arAccount->id);
        $this->assertEquals(1100.00, (float) $arLine->debit);
        $this->assertEquals(1000.00, (float) $arLine->foreign_amount);
        $this->assertEquals('EUR', $arLine->foreign_currency);
        $this->assertEquals(1.10, (float) $arLine->exchange_rate);

        $revLine = $lines->firstWhere('account_id', $this->incomeAccount->id);
        $this->assertEquals(1100.00, (float) $revLine->credit);
    }

    public function test_base_currency_invoice_no_fx_fields_set(): void
    {
        $invoiceService = app(InvoiceService::class);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'currency' => 'USD',
            'lines' => [
                [
                    'description' => 'Services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $invoice = $invoiceService->post($invoice, $this->userId);

        $this->assertEquals(1.00, (float) $invoice->exchange_rate);
        $this->assertEquals(1000.00, (float) $invoice->base_amount);
    }

    public function test_realized_gain_loss_on_customer_payment(): void
    {
        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.1000,
            'effective_date' => '2026-01-15',
        ]);

        $invoiceService = app(InvoiceService::class);
        $paymentService = app(PaymentService::class);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'currency' => 'EUR',
            'lines' => [
                [
                    'description' => 'Services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $invoice = $invoiceService->post($invoice, $this->userId);

        $payment = $paymentService->createCustomerPayment([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_date' => '2026-02-20',
            'amount' => 1100,
            'bank_account_id' => $this->cashAccount->id,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 1100],
            ],
        ], $this->userId);

        $payment = $paymentService->postCustomerPayment($payment, $this->userId);

        $this->assertNotNull($payment->journal_entry_id);

        $gainLossEntries = JournalEntry::where('source_module', 'realized_fx_gain_loss')->get();

        $this->assertCount(0, $gainLossEntries);

        $this->assertEquals(1100.00, (float) $invoice->fresh()->amount_paid);
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_unrealized_revaluation_posted(): void
    {
        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.1000,
            'effective_date' => '2026-01-15',
        ]);

        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.1500,
            'effective_date' => '2026-03-15',
        ]);

        $invoiceService = app(InvoiceService::class);

        $invoice = $invoiceService->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-02-15',
            'due_date' => '2026-04-15',
            'currency' => 'EUR',
            'lines' => [
                [
                    'description' => 'Services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $invoice = $invoiceService->post($invoice, $this->userId);

        $fxService = app(ForeignCurrencyService::class);

        $openInvoices = Invoice::where('company_id', $this->company->id)
            ->whereIn('status', [\App\Models\Invoice::STATUS_SENT, \App\Models\Invoice::STATUS_PARTIALLY_PAID])
            ->where('currency', '!=', 'USD')
            ->get();

        $this->assertCount(1, $openInvoices, 'Should find 1 open foreign-currency invoice');

        $inv = $openInvoices->first();
        $this->assertEquals(1000.00, (float) $inv->amount);
        $this->assertEquals(1100.00, (float) $inv->base_amount);
        $this->assertEquals('EUR', $inv->currency);

        $converted = $fxService->convert($this->company->id, 1000, 'EUR', 'USD', '2026-03-31');
        $this->assertEquals(1150.00, $converted);

        $gainLoss = $fxService->revalueForeignBalances($this->company->id, $this->userId, '2026-03-31');

        $this->assertNotEquals(0, $gainLoss);

        $revalEntry = JournalEntry::where('source_module', 'unrealized_fx_revaluation')->first();
        $this->assertNotNull($revalEntry);

        $lines = $revalEntry->lines()->get();
        $this->assertCount(2, $lines);

        $debitTotal = $lines->sum('debit');
        $creditTotal = $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $fxLine = $lines->firstWhere('account_id', $this->unrealizedFxAccount->id);
        $this->assertNotNull($fxLine);
        $this->assertGreaterThan(0, max((float) $fxLine->debit, (float) $fxLine->credit));
    }

    public function test_convert_uses_latest_rate_before_date(): void
    {
        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'GBP',
            'currency_to' => 'USD',
            'rate' => 1.2500,
            'effective_date' => '2026-01-01',
        ]);

        $fxService = app(ForeignCurrencyService::class);

        $result = $fxService->convert($this->company->id, 1000, 'GBP', 'USD', '2026-02-15');
        $this->assertEquals(1250.00, $result);
    }

    public function test_convert_same_currency_returns_amount(): void
    {
        $fxService = app(ForeignCurrencyService::class);

        $result = $fxService->convert($this->company->id, 500, 'USD', 'USD', '2026-02-15');
        $this->assertEquals(500.00, $result);
    }

    public function test_convert_throws_on_missing_rate(): void
    {
        $fxService = app(ForeignCurrencyService::class);

        $this->expectException(\InvalidArgumentException::class);

        $fxService->convert($this->company->id, 1000, 'JPY', 'USD', '2026-02-15');
    }
}
