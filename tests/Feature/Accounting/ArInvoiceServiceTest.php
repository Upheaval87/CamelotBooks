<?php

namespace Tests\Feature\Accounting;

use App\Mail\InvoiceMail;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AccountAuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Quotation;
use App\Services\Accounting\ArInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * INVOICES MODULE §5 — the AR orchestration layer.
 *
 * These tests drive `ArInvoiceService` directly. The legacy
 * `InvoiceServiceTest` already covers the ledger maths that this service
 * delegates to; what matters here is the AR behaviour layered on top: the
 * derived status, immutable receipts, the split gate, walk-in capture,
 * quotation provenance, the post-time auto-email and the collections queue.
 */
class ArInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ArInvoiceService $service;

    protected Company $company;

    protected Account $arAccount;

    protected Account $incomeAccount;

    protected Account $taxPayableAccount;

    protected Customer $customer;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ArInvoiceService::class);

        $user = \App\Models\User::factory()->create();
        $this->userId = $user->id;

        $this->company = Company::create([
            'name' => 'AR Test Company',
            'company_code' => 'ARTEST',
            'is_active' => true,
        ]);

        // Deliberately wider than any date these tests use: the engine matches periods
        // by string comparison, so a fixture period that starts exactly on the
        // test date fails the `start_date <=` bound.
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => 'Long window',
            'start_date' => '2025-01-01',
            'end_date' => '2027-12-31',
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

        Account::create([
            'company_id' => $this->company->id,
            'code' => '9999',
            'name' => 'Rounding Differences',
            'type' => 'expense',
            'sub_type' => 'non_operating_expense',
            'is_active' => true,
        ]);

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');

        foreach ([
            'accounts_receivable' => '1100',
            'default_revenue' => '4000',
            'tax_payable' => '2300',
            'rounding' => '9999',
        ] as $key => $code) {
            \App\Models\DefaultAccountMapping::setMapping(
                $this->company->id,
                $key,
                $accounts[$code]->id
            );
        }

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Registered Customer',
            'display_name' => 'Reg Customer',
            'email' => 'accounts@registered.test',
            'phone' => '+265 111 000 111',
            'billing_address' => 'Plot 1, Lilongwe',
            'payment_terms' => 'net_30',
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------- fixtures

    /** @param array<string, mixed> $overrides */
    protected function invoiceData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'lines' => [
                [
                    'description' => 'Consulting services',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    // Lifecycle/status tests keep tax-free arithmetic; the
                    // null -> 16.5% spec fallback has its own test below.
                    'tax_rate' => 0,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $overrides);
    }

    /** @param array<string, mixed> $overrides */
    protected function postInvoice(array $overrides = []): Invoice
    {
        return $this->service->post(
            $this->service->create($this->invoiceData($overrides), $this->userId, $this->company->id),
            $this->userId
        );
    }

    // ------------------------------------------------------------ lifecycle

    public function test_create_with_mode_post_posts_in_one_call(): void
    {
        Mail::fake();

        $invoice = $this->service->create(
            $this->invoiceData(['mode' => 'post']),
            $this->userId,
            $this->company->id
        );

        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertNotNull($invoice->journal_entry_id);
    }

    public function test_create_defaults_to_draft(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData(),
            $this->userId,
            $this->company->id
        );

        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertNull($invoice->journal_entry_id);
    }

    public function test_issue_date_is_written_to_both_legacy_and_ar_columns(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData(['issue_date' => '2026-04-17']),
            $this->userId,
            $this->company->id
        );

        $fresh = $invoice->fresh();

        // `issue_date` is the AR-facing name, `invoice_date` the legacy one.
        // They are two columns for one fact and must never drift apart.
        $this->assertSame('2026-04-17', $fresh->issue_date->toDateString());
        $this->assertSame('2026-04-17', $fresh->invoice_date->toDateString());
    }

    public function test_due_date_follows_customer_terms_when_not_supplied(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData(['issue_date' => '2026-04-17']),
            $this->userId,
            $this->company->id
        );

        // The fixture customer is net_30.
        $this->assertSame('2026-05-17', $invoice->fresh()->due_date->toDateString());
    }

    public function test_contact_block_is_filled_from_the_selected_customer(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData(),
            $this->userId,
            $this->company->id
        )->fresh();

        $this->assertSame('Registered Customer', $invoice->customer_name);
        $this->assertSame('accounts@registered.test', $invoice->email);
        $this->assertSame('Plot 1, Lilongwe', $invoice->bill_address);
        $this->assertSame('net_30', $invoice->terms_key);
        $this->assertSame(Invoice::SOURCE_MANUAL, $invoice->source_type);
    }

    public function test_totals_are_persisted_alongside_the_legacy_amount(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData([
                'lines' => [
                    [
                        'description' => 'Taxed line',
                        'quantity' => 2,
                        'unit_price' => 500,
                        'tax_rate' => 10,
                        'income_account_id' => $this->incomeAccount->id,
                    ],
                ],
            ]),
            $this->userId,
            $this->company->id
        )->fresh();

        $this->assertSame(1000.00, (float) $invoice->subtotal);
        $this->assertSame(100.00, (float) $invoice->tax_total);
        $this->assertSame(1100.00, (float) $invoice->grand_total);
        // The legacy column is kept in lockstep for the customer centre.
        $this->assertSame(1100.00, (float) $invoice->amount);
    }

    public function test_missing_tax_rate_falls_back_to_the_spec_default(): void
    {
        // The spec create form posts no tax input on a new row, so the rate
        // arrives null/'' and must pick up the mock's fixed 16.5%.
        $invoice = $this->service->create(
            $this->invoiceData([
                'lines' => [
                    [
                        'description' => 'Untyped tax line',
                        'quantity' => 1,
                        'unit_price' => 1000,
                        'income_account_id' => $this->incomeAccount->id,
                    ],
                ],
            ]),
            $this->userId,
            $this->company->id
        )->fresh();

        $this->assertSame(1000.00, (float) $invoice->subtotal);
        $this->assertSame(165.00, (float) $invoice->tax_total);
        $this->assertSame(1165.00, (float) $invoice->grand_total);
    }

    public function test_posted_invoice_cannot_be_edited(): void
    {
        $invoice = $this->postInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft invoices can be edited');

        $this->service->update($invoice, $this->invoiceData(), $this->userId);
    }

    public function test_draft_invoice_can_be_edited(): void
    {
        $invoice = $this->service->create($this->invoiceData(), $this->userId, $this->company->id);

        $updated = $this->service->update($invoice, [
            'issue_date' => '2026-05-01',
            'lines' => [
                [
                    'description' => 'Revised scope',
                    'quantity' => 1,
                    'unit_price' => 2500,
                    'tax_rate' => 0,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $this->assertSame(2500.00, (float) $updated->grand_total);
        $this->assertSame('2026-05-01', $updated->issue_date->toDateString());
    }

    public function test_edit_cannot_repoint_a_converted_quotation(): void
    {
        // The flag is only ever set by posting (5.2), so a quotation being converted
        // must still be clean at create time — seeding `invoiced_flag` here would
        // be rejected up front rather than exercised.
        $quotation = $this->makeQuotation();

        $invoice = $this->service->create(
            $this->invoiceData(['source_quotation_id' => $quotation->id]),
            $this->userId,
            $this->company->id
        );

        // R19 — the picker is create-only, so an edit must not silently move the
        // document onto a different source.
        $updated = $this->service->update($invoice, [
            'issue_date' => now()->format('Y-m-d'),
            'source_quotation_id' => null,
            'lines' => [
                [
                    'description' => 'Edited',
                    'quantity' => 1,
                    'unit_price' => 10,
                    'income_account_id' => $this->incomeAccount->id,
                ],
            ],
        ], $this->userId);

        $this->assertSame($quotation->id, (int) $updated->source_quotation_id);
        $this->assertSame(Invoice::SOURCE_QUOTATION, $updated->source_type);
    }

    public function test_void_is_blocked_once_a_receipt_exists(): void
    {
        $invoice = $this->postInvoice();

        $this->service->recordPayment($invoice, [
            'amount' => 100,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('raise a credit note');

        $this->service->void($invoice->fresh(), 'operator error', $this->userId);
    }

    public function test_void_without_receipts_reverses_the_ledger(): void
    {
        $invoice = $this->postInvoice();
        $entryId = $invoice->journal_entry_id;

        $voided = $this->service->void($invoice, 'duplicate raised', $this->userId);

        $this->assertSame(Invoice::STATUS_VOID, $voided->status);
        $this->assertSame(
            \App\Models\JournalEntry::STATUS_REVERSED,
            \App\Models\JournalEntry::findOrFail($entryId)->status
        );
    }

    // -------------------------------------------------------- post gate (8.3)

    public function test_post_gate_fails_on_a_blank_party_despite_the_walkin_fallback(): void
    {
        $invoice = new Invoice(['company_id' => $this->company->id]);
        $invoice->due_date = now();
        $invoice->setRelation('lines', collect());

        $gate = $this->service->postGate($invoice);

        // `partyName()` falls back to "Walk-in Customer", so a gate built on it
        // could never fail — it must read the raw party instead.
        $this->assertFalse($gate['checks']['customer']['ok']);
        $this->assertFalse($gate['allowed']);
    }

    public function test_post_gate_accepts_a_complete_document(): void
    {
        $invoice = $this->service->create($this->invoiceData(), $this->userId, $this->company->id);

        $gate = $this->service->postGate($invoice);

        $this->assertTrue($gate['allowed']);
        $this->assertTrue($gate['checks']['customer']['ok']);
        $this->assertTrue($gate['checks']['due_date']['ok']);
        $this->assertTrue($gate['checks']['lines']['ok']);
    }

    // -------------------------------------------------------------- receipts

    public function test_partial_payment_moves_status_to_partially_paid(): void
    {
        $invoice = $this->postInvoice();

        $payment = $this->service->recordPayment($invoice, [
            'amount' => 400,
            'method' => InvoicePayment::METHOD_BANK_TRANSFER,
            'details' => ['bank' => 'National Bank', 'txref' => 'NB-1'],
        ], $this->userId);

        $this->assertMatchesRegularExpression('/^RC-\d{4}-0001$/', $payment->receipt_no);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PARTIALLY_PAID, $invoice->status);
        $this->assertSame(400.00, (float) $invoice->amount_paid);
        // `settled` is read by the customer centre and statements.
        $this->assertSame(400.00, (float) $invoice->settled);
        $this->assertSame(600.00, $invoice->balance_due);
        $this->assertSame('partial', $invoice->arStatus());
    }

    public function test_receipts_numbering_is_per_company_and_sequential(): void
    {
        $invoice = $this->postInvoice();

        $first = $this->service->recordPayment($invoice, [
            'amount' => 100,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);

        $second = $this->service->recordPayment($invoice->fresh(), [
            'amount' => 100,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);

        $this->assertNotSame($first->receipt_no, $second->receipt_no);
        $this->assertSame(
            1,
            (int) substr($first->receipt_no, -4),
        );
        $this->assertSame(2, (int) substr($second->receipt_no, -4));
    }

    public function test_full_payment_moves_status_to_paid(): void
    {
        $invoice = $this->postInvoice();

        $this->service->recordPayment($invoice, [
            'amount' => 1000,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);

        $invoice->refresh();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(0.0, $invoice->balance_due);
        $this->assertSame('paid', $invoice->arStatus());
        // A settled invoice is never overdue, even with a past due date.
        $this->assertFalse($invoice->isOverdue());
    }

    public function test_payment_exceeding_the_balance_is_rejected(): void
    {
        $invoice = $this->postInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the balance due');

        $this->service->recordPayment($invoice, [
            'amount' => 1500,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);
    }

    public function test_zero_payment_is_rejected(): void
    {
        $invoice = $this->postInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than zero');

        $this->service->recordPayment($invoice, [
            'amount' => 0,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);
    }

    public function test_payments_are_refused_on_a_draft_invoice(): void
    {
        $draft = $this->service->create($this->invoiceData(), $this->userId, $this->company->id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only be recorded against a posted invoice');

        $this->service->recordPayment($draft, [
            'amount' => 10,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);
    }

    public function test_mobile_money_receipt_requires_a_phone(): void
    {
        $invoice = $this->postInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('phone number');

        $this->service->recordPayment($invoice, [
            'amount' => 100,
            'method' => InvoicePayment::METHOD_MOBILE_MONEY,
            'details' => ['provider' => 'Airtel Money'],
        ], $this->userId);
    }

    public function test_cheque_receipt_requires_bank_and_number(): void
    {
        $invoice = $this->postInvoice();

        try {
            $this->service->recordPayment($invoice, [
                'amount' => 100,
                'method' => InvoicePayment::METHOD_CHEQUE,
                'details' => ['bank' => 'Standard Bank'],
            ], $this->userId);

            $this->fail('Expected a missing-cheque-number rejection.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cheque number', $e->getMessage());
        }
    }

    public function test_split_legs_must_sum_to_the_payment_amount(): void
    {
        $invoice = $this->postInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Split legs add up to');

        $this->service->recordPayment($invoice, [
            'amount' => 500,
            'method' => InvoicePayment::METHOD_SPLIT,
            'splits' => [
                ['method' => InvoicePayment::METHOD_CASH, 'amount' => 200],
                ['method' => InvoicePayment::METHOD_CARD, 'amount' => 200],
            ],
        ], $this->userId);
    }

    public function test_valid_split_receipt_is_stored_with_its_legs(): void
    {
        $invoice = $this->postInvoice();

        $payment = $this->service->recordPayment($invoice, [
            'amount' => 500,
            'method' => InvoicePayment::METHOD_SPLIT,
            'splits' => [
                ['method' => InvoicePayment::METHOD_CASH, 'amount' => 200],
                [
                    'method' => InvoicePayment::METHOD_MOBILE_MONEY,
                    'amount' => 300,
                    'details' => ['phone' => '+265 999 000 000'],
                ],
            ],
        ], $this->userId);

        $splits = $payment->splits;

        $this->assertCount(2, $splits);
        $this->assertSame(InvoicePayment::METHOD_CASH, $splits[0]['method']);
        $this->assertSame(InvoicePayment::METHOD_MOBILE_MONEY, $splits[1]['method']);
        $this->assertSame(500.0, (float) $invoice->fresh()->amount_paid);
    }

    public function test_a_split_leg_may_not_itself_be_a_split(): void
    {
        $invoice = $this->postInvoice();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only nest one level');

        $this->service->recordPayment($invoice, [
            'amount' => 100,
            'method' => InvoicePayment::METHOD_SPLIT,
            'splits' => [
                ['method' => InvoicePayment::METHOD_SPLIT, 'amount' => 100],
            ],
        ], $this->userId);
    }

    public function test_receipt_is_audited(): void
    {
        $invoice = $this->postInvoice();

        $this->service->recordPayment($invoice, [
            'amount' => 100,
            'method' => InvoicePayment::METHOD_CASH,
        ], $this->userId);

        $audit = AccountAuditLog::where('journalable_type', Invoice::class)
            ->where('journalable_id', $invoice->id)
            ->where('action', 'payment_receipted')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals(100.0, $audit->new_values['amount']);
        $this->assertSame(Invoice::STATUS_PARTIALLY_PAID, $audit->new_values['status']);
    }

    // ------------------------------------------------------------- walk-in (R12)

    public function test_walk_in_capture_creates_a_real_customer_row(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData([
                'customer_id' => null,
                'customer_name' => 'Walk-in Client',
                'contact_person' => 'The Operator',
                'email' => 'walkin@example.test',
                'phone' => '+265 000 111 222',
            ]),
            $this->userId,
            $this->company->id
        )->fresh();

        $this->assertNotNull($invoice->customer_id);

        $customer = Customer::findOrFail($invoice->customer_id);
        $this->assertSame('Walk-in Client', $customer->name);
        $this->assertSame('The Operator', $customer->display_name);
        $this->assertSame('walkin@example.test', $customer->email);
        $this->assertTrue($customer->is_active);

        // The invoice keeps the typed name too, so the document is self-contained.
        $this->assertSame('Walk-in Client', $invoice->customer_name);
    }

    public function test_walk_in_capture_is_audited_against_the_customer(): void
    {
        $this->service->create(
            $this->invoiceData(['customer_id' => null, 'customer_name' => 'Audited Walk-in']),
            $this->userId,
            $this->company->id
        );

        $audit = AccountAuditLog::where('action', 'walk_in_customer_captured')->first();

        // At capture time no invoice row exists, so the customer is the subject.
        $this->assertNotNull($audit);
        $this->assertSame(Customer::class, $audit->journalable_type);
        $this->assertSame('Audited Walk-in', $audit->new_values['customer_name']);
    }

    public function test_customer_with_no_name_leaves_the_invoice_without_a_party(): void
    {
        $invoice = $this->service->create(
            $this->invoiceData(['customer_id' => null, 'customer_name' => '   ']),
            $this->userId,
            $this->company->id
        )->fresh();

        $this->assertNull($invoice->customer_id);
        $this->assertFalse($this->service->postGate($invoice->fresh(['lines', 'customer']))['allowed']);
    }

    // ------------------------------------------------- quotation provenance (R13)

    public function test_posting_a_converted_invoice_marks_the_quotation(): void
    {
        $quotation = $this->makeQuotation();

        $invoice = $this->service->post(
            $this->service->create(
                $this->invoiceData(['source_quotation_id' => $quotation->id]),
                $this->userId,
                $this->company->id
            ),
            $this->userId
        );

        $quotation->refresh();

        $this->assertSame(Invoice::SOURCE_QUOTATION, $invoice->source_type);
        $this->assertSame($quotation->quotation_number, $invoice->source_ref);
        $this->assertTrue($quotation->invoiced_flag);
        $this->assertSame($invoice->id, $quotation->invoice_id);
        // The legacy conversion column the quotations module still renders.
        $this->assertSame($invoice->id, $quotation->converted_invoice_id);
    }

    public function test_an_already_invoiced_quotation_cannot_be_reused(): void
    {
        // Reuse means "a quotation that already produced an invoice", so convert one for
        // real first rather than hand-writing the flag — a hand-written
        // `invoice_id => 999` would trip the foreign key instead of exercising
        // the guard this test is about.
        $quotation = $this->makeQuotation();

        $invoice = $this->service->create(
            $this->invoiceData(['source_quotation_id' => $quotation->id]),
            $this->userId,
            $this->company->id
        );
        $this->service->post($invoice, $this->userId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already been invoiced');

        $this->service->create(
            $this->invoiceData(['source_quotation_id' => $quotation->id]),
            $this->userId,
            $this->company->id
        );
    }

    public function test_a_missing_quotation_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no longer exists');

        $this->service->create(
            $this->invoiceData(['source_quotation_id' => 999999]),
            $this->userId,
            $this->company->id
        );
    }

    public function test_draft_invoice_does_not_mark_the_quotation(): void
    {
        $quotation = $this->makeQuotation();

        $this->service->create(
            $this->invoiceData(['source_quotation_id' => $quotation->id]),
            $this->userId,
            $this->company->id
        );

        // The flag flips on POST, not on save — a draft is still editable.
        $this->assertFalse($quotation->fresh()->invoiced_flag);
    }

    public function test_conversion_is_audited(): void
    {
        $quotation = $this->makeQuotation();

        $this->service->post(
            $this->service->create(
                $this->invoiceData(['source_quotation_id' => $quotation->id]),
                $this->userId,
                $this->company->id
            ),
            $this->userId
        );

        $this->assertTrue(
            AccountAuditLog::where('action', 'quotation_converted')->exists()
        );
    }

    public function test_uninvoiced_quotations_picker_excludes_converted_and_unaccepted(): void
    {
        $open = $this->makeQuotation(['quotation_number' => 'QT-OPEN']);
        $converted = $this->makeQuotation([
            'quotation_number' => 'QT-DONE',
            'invoiced_flag' => true,
        ]);
        $draft = $this->makeQuotation([
            'quotation_number' => 'QT-DRAFT',
            'status' => Quotation::STATUS_DRAFT,
        ]);

        $results = collect($this->service->uninvoicedQuotations($this->company->id));
        $ids = $results->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($converted->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    // -------------------------------------------------------------- email (R17)

    public function test_posting_auto_emails_when_a_valid_address_exists(): void
    {
        Mail::fake();

        $invoice = $this->postInvoice()->fresh();

        $this->assertSame('accounts@registered.test', $invoice->emailed_to);
        $this->assertNotNull($invoice->emailed_at);
        Mail::assertQueued(InvoiceMail::class);
    }

    public function test_posting_does_not_email_when_the_address_is_invalid(): void
    {
        Mail::fake();

        $this->customer->update(['email' => 'not-an-address']);

        $invoice = $this->postInvoice()->fresh();

        // A bad address must not block posting — the ledger is already touched.
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertNull($invoice->emailed_to);
        Mail::assertNothingQueued();
    }

    public function test_posting_does_not_email_when_no_address_exists(): void
    {
        Mail::fake();

        $this->customer->update(['email' => null]);

        $invoice = $this->postInvoice()->fresh();

        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
        $this->assertNull($invoice->emailed_to);
        Mail::assertNothingQueued();
    }

    public function test_send_email_stamps_and_queues(): void
    {
        Mail::fake();

        $invoice = $this->postInvoice(['customer_id' => null, 'customer_name' => 'No Email Party']);

        // An explicit payload address is honoured even with no customer email.
        $invoice->update(['email' => 'party@example.test']);

        $result = $this->service->sendEmail($invoice, $this->userId);

        $this->assertTrue($result['emailed']);
        $this->assertSame('party@example.test', $result['recipient']);
        $this->assertSame('party@example.test', $invoice->fresh()->emailed_to);
        Mail::assertQueued(InvoiceMail::class);
    }

    public function test_send_email_is_refused_without_an_address(): void
    {
        Mail::fake();

        $this->customer->update(['email' => null]);

        $invoice = $this->service->post(
            $this->service->create(
                $this->invoiceData(['email' => null]),
                $this->userId,
                $this->company->id
            ),
            $this->userId
        );
        $invoice->forceFill(['email' => null])->saveQuietly();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no email address');

        $this->service->sendEmail($invoice->fresh(), $this->userId);
    }

    public function test_send_email_is_refused_for_a_draft(): void
    {
        Mail::fake();

        $draft = $this->service->create($this->invoiceData(), $this->userId, $this->company->id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only sent, partially paid or paid');

        $this->service->sendEmail($draft, $this->userId);
    }

    public function test_email_dispatch_is_audited(): void
    {
        Mail::fake();

        $this->postInvoice();

        $this->assertTrue(
            AccountAuditLog::where('action', 'emailed')
                ->where('journalable_type', Invoice::class)
                ->exists()
        );
    }

    public function test_failed_delivery_retracts_the_emailed_stamp(): void
    {
        $invoice = $this->postInvoice()->fresh();

        $this->assertNotNull($invoice->emailed_to);

        // Simulate every queue attempt failing.
        (new InvoiceMail($invoice))->failed(new \RuntimeException('SMTP refused'));

        $invoice->refresh();

        $this->assertNull($invoice->emailed_to);
        $this->assertNull($invoice->emailed_at);

        $failure = AccountAuditLog::where('action', 'email_failed')->first();
        $this->assertNotNull($failure);
        $this->assertSame('SMTP refused', $failure->notes);
        // The retracted values are captured BEFORE the clear.
        $this->assertSame('accounts@registered.test', $failure->old_values['emailed_to']);
    }

    // ---------------------------------------------------- derived state (5.4)

    public function test_overdue_is_derived_and_never_stored(): void
    {
        $invoice = $this->postInvoice([
            'issue_date' => now()->subDays(60)->format('Y-m-d'),
            'due_date' => now()->subDays(30)->format('Y-m-d'),
        ])->fresh();

        $this->assertSame('sent', $invoice->status);
        $this->assertSame('overdue', $invoice->arStatus());
        $this->assertTrue($invoice->isOverdue());
        $this->assertSame(30, $invoice->daysOverdue());
        $this->assertSame('days_1_30', $invoice->agingBucket());
    }

    public function test_legacy_stored_overdue_is_re_derived_on_read(): void
    {
        $invoice = $this->postInvoice([
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ])->fresh();

        // Simulate a legacy row that stored 'overdue'.
        $invoice->forceFill(['status' => Invoice::STATUS_OVERDUE])->saveQuietly();

        $this->assertSame('overdue', $invoice->fresh()->arStatus());
        // A paid invoice is never overdue, even on a legacy row.
        $invoice->forceFill(['status' => Invoice::STATUS_OVERDUE, 'amount_paid' => 1000])->saveQuietly();
        $this->assertSame('paid', $invoice->fresh()->arStatus());
    }

    // -------------------------------------------------------- collections queue

    public function test_collections_queue_returns_only_open_balances_worst_first(): void
    {
        $query = Invoice::query()->where('company_id', $this->company->id);

        $current = $this->postInvoice([
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(40)->format('Y-m-d'),
        ]);

        $mild = $this->postInvoice([
            'issue_date' => now()->subDays(40)->format('Y-m-d'),
            'due_date' => now()->subDays(10)->format('Y-m-d'),
        ]);

        $severe = $this->postInvoice([
            'issue_date' => now()->subDays(90)->format('Y-m-d'),
            'due_date' => now()->subDays(45)->format('Y-m-d'),
        ]);

        // A draft and a settled invoice are not collectable.
        $this->service->create($this->invoiceData(), $this->userId, $this->company->id);
        $this->service->recordPayment($severe, ['amount' => 1000, 'method' => InvoicePayment::METHOD_CASH], $this->userId);

        $result = $this->service->collectionsQueue($query);
        $ids = $result['invoices']->pluck('id')->all();

        $this->assertSame([$mild->id, $current->id], $ids);
        $this->assertSame(2000.0, $result['totals']['outstanding_ar']);
        $this->assertSame(1, $result['totals']['overdue_count']);
        $this->assertSame(1000.0, $result['totals']['overdue_value']);
        $this->assertSame(2, $result['totals']['count']);
    }

    public function test_collections_queue_counts_balances_due_within_seven_days(): void
    {
        $query = Invoice::query()->where('company_id', $this->company->id);

        $this->postInvoice([
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $result = $this->service->collectionsQueue($query);

        $this->assertSame(1000.0, $result['totals']['due_7_value']);
        $this->assertSame(1, $result['totals']['due_7_count']);
        $this->assertSame(0, $result['totals']['overdue_count']);
    }

    public function test_collections_queue_paginates(): void
    {
        $query = Invoice::query()->where('company_id', $this->company->id);

        $this->postInvoice(['due_date' => now()->addDays(2)->format('Y-m-d')]);
        $this->postInvoice(['due_date' => now()->addDays(3)->format('Y-m-d')]);
        $this->postInvoice(['due_date' => now()->addDays(4)->format('Y-m-d')]);

        $page = $this->service->collectionsQueue($query, 2, 2);

        $this->assertCount(1, $page['invoices']->items());
        $this->assertSame(3, $page['invoices']->total());
        $this->assertSame(2, $page['invoices']->currentPage());
    }

    // ------------------------------------------------------------- register

    public function test_register_filters_by_status_and_view(): void
    {
        $sent = $this->postInvoice([
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $this->postInvoice([
            'issue_date' => now()->subDays(90)->format('Y-m-d'),
            'due_date' => now()->subDays(60)->format('Y-m-d'),
        ]);

        $this->service->recordPayment($sent, ['amount' => 400, 'method' => InvoicePayment::METHOD_CASH], $this->userId);

        $overdueIds = $this->service->registerQuery(['status' => 'overdue'], $this->company->id)
            ->pluck('id')->all();
        $this->assertCount(1, $overdueIds);

        $openCount = $this->service->registerQuery(['view' => 'open'], $this->company->id)->count();
        $this->assertSame(2, $openCount);

        $draftCount = $this->service->registerQuery(['view' => 'draft'], $this->company->id)->count();
        $this->assertSame(0, $draftCount);
    }

    public function test_register_search_matches_number_customer_and_reference(): void
    {
        $invoice = $this->postInvoice([
            'reference' => 'PO-GB-118',
        ]);

        $byNumber = $this->service->registerQuery(['q' => $invoice->invoice_number], $this->company->id)->count();
        $this->assertSame(1, $byNumber);

        $byCustomer = $this->service->registerQuery(['q' => 'Registered'], $this->company->id)->count();
        $this->assertSame(1, $byCustomer);

        $byReference = $this->service->registerQuery(['q' => 'PO-GB'], $this->company->id)->count();
        $this->assertSame(1, $byReference);

        $byNothing = $this->service->registerQuery(['q' => 'zzz-no-match'], $this->company->id)->count();
        $this->assertSame(0, $byNothing);
    }

    public function test_register_search_finds_a_walk_in_invoice_by_typed_name(): void
    {
        $this->service->create(
            $this->invoiceData(['customer_id' => null, 'customer_name' => 'Zebedee Mwale']),
            $this->userId,
            $this->company->id
        );

        // Walk-in rows have no customer relation, so the typed name must be
        // searchable on the invoice itself.
        $this->assertSame(
            1,
            $this->service->registerQuery(['q' => 'Zebedee'], $this->company->id)->count()
        );
    }

    // ---------------------------------------------------------------- helpers

    /** @param array<string, mixed> $overrides */
    protected function makeQuotation(array $overrides = []): Quotation
    {
        return Quotation::create(array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'customer_name' => 'Registered Customer',
            'quotation_number' => 'QT-' . $this->company->id . '-' . Quotation::count() + 1,
            'status' => Quotation::STATUS_ACCEPTED,
            'quotation_date' => now()->subDays(3)->format('Y-m-d'),
            'valid_until' => now()->addDays(10)->format('Y-m-d'),
            'subtotal' => 1000,
            'tax_total' => 0,
            'total' => 1000,
            'currency' => 'USD',
            'invoiced_flag' => false,
            'created_by' => $this->userId,
        ], $overrides));
    }
}