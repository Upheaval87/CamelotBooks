<?php

namespace Tests\Feature\Accounting;

use App\Jobs\SendQuotationEmail;
use App\Models\AccountingPeriod;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationEmailJob;
use App\Services\Accounting\QuotationEmailDispatchService;
use App\Services\Accounting\QuotationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * §2–§4 — the email dispatch subsystem.
 *
 * Covers the endpoints the progress modal/dock talk to (202 + job contract,
 * retry, status, cancel), the four-stage pipeline itself, and the durable state
 * the register renders: the quotation stamp, the failure retraction, and the
 * audit lineage the needs-attention rail derives from.
 */
class QuotationEmailDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected AccountingPeriod $period;

    protected Account $revenueAccount;

    protected Customer $customer;

    protected Product $product;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();

        $this->company = Company::create([
            'name' => 'Quotation Dispatch Co',
            'company_code' => 'QDC',
            'is_active' => true,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        $this->seed(RolePermissionSeeder::class);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->user->update(['current_company_id' => $this->company->id]);
        session(['current_company_id' => $this->company->id]);

        $this->period = AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        $this->revenueAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'quotation',
            'prefix' => 'QT-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'annually',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Retail Ltd',
            'display_name' => 'Acme Retail Ltd',
            'email' => 'billing@acme.test',
            'phone' => '+1 555 0100',
            'payment_terms' => 'net-30',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Ergonomic Chair',
            'sku' => 'CHAIR-01',
            'type' => 'service',
            'sales_price' => 150,
            'purchase_price' => 90,
            'income_account_id' => $this->revenueAccount->id,
            'expense_account_id' => $this->revenueAccount->id,
            'tax_rate' => 16.5,
            'is_taxable' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-02-15',
            'valid_until' => '2026-02-28',
            'currency' => 'MWK',
            'reference' => 'RFQ-009',
            'memo' => 'Please include delivery.',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Ergonomic Chair',
                    'quantity' => 2,
                    'unit_price' => 150,
                    'discount' => 10,
                    'tax_rate' => 16.5,
                    'income_account_id' => $this->revenueAccount->id,
                ],
            ],
        ], $overrides);
    }

    protected function sentQuotation(): Quotation
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        return app(QuotationService::class)->send($quotation);
    }

    /* ------------------------------------------------------------------
     * §2.2 / §2.3 — the endpoint contract
     * ------------------------------------------------------------------ */

    public function test_email_endpoint_returns_202_with_the_job_contract(): void
    {
        Bus::fake();
        Mail::fake();

        $quotation = $this->sentQuotation();

        $response = $this->postJson(route('accounting.quotations.email', $quotation));

        $response->assertStatus(202)
            ->assertJsonPath('status', QuotationEmailJob::STATUS_RUNNING)
            ->assertJsonPath('stage', 'preparing')
            ->assertJsonPath('stage_label', 'Preparing PDF document')
            ->assertJsonPath('recipient', 'billing@acme.test')
            ->assertJsonPath('percent', 22)
            ->assertJsonPath('attempts', 1)
            ->assertJsonPath('error_message', null)
            ->assertJsonStructure([
                'job_id', 'status', 'stage', 'stage_label', 'percent', 'recipient',
                'attempts', 'error_code', 'error_message', 'started_at', 'finished_at',
                'status_url', 'cancel_url', 'quotation',
            ]);

        $jobId = $response->json('job_id');
        $this->assertSame(route('accounting.email-jobs.show', $jobId), $response->json('status_url'));
        $this->assertSame(route('accounting.email-jobs.cancel', $jobId), $response->json('cancel_url'));
        $this->assertSame($quotation->id, $response->json('quotation.id'));
        $this->assertSame($quotation->quotation_number, $response->json('quotation.number'));

        $this->assertDatabaseHas('email_jobs', [
            'id' => $jobId,
            'company_id' => $this->company->id,
            'quotable_type' => Quotation::class,
            'quotable_id' => $quotation->id,
            'recipient' => 'billing@acme.test',
            'status' => QuotationEmailJob::STATUS_RUNNING,
            'attempts' => 1,
        ]);

        // The pipeline is queued, not run inline — the browser sees real stages.
        Bus::assertDispatched(SendQuotationEmail::class);
    }

    public function test_email_endpoint_honours_an_explicit_recipient(): void
    {
        Bus::fake();

        $quotation = $this->sentQuotation();

        $this->postJson(route('accounting.quotations.email', $quotation), ['email' => 'ap@acme.test'])
            ->assertStatus(202)
            ->assertJsonPath('recipient', 'ap@acme.test');

        $this->assertDatabaseHas('email_jobs', ['quotable_id' => $quotation->id, 'recipient' => 'ap@acme.test']);
    }

    public function test_email_endpoint_refuses_a_draft(): void
    {
        Bus::fake();

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $this->postJson(route('accounting.quotations.email', $quotation))
            ->assertStatus(422)
            ->assertJsonPath('error', 'draft');

        $this->assertSame(0, QuotationEmailJob::count());
        Bus::assertNothingDispatched();
    }

    public function test_email_endpoint_refuses_a_void_quotation(): void
    {
        Bus::fake();

        $quotation = $this->sentQuotation();
        $quotation->update(['status' => Quotation::STATUS_VOID]);

        $this->postJson(route('accounting.quotations.email', $quotation))
            ->assertStatus(422)
            ->assertJsonPath('error', 'void');

        $this->assertSame(0, QuotationEmailJob::count());
    }

    public function test_email_endpoint_refuses_a_malformed_recipient(): void
    {
        Bus::fake();

        $quotation = $this->sentQuotation();
        $this->customer->update(['email' => 'jane@mail.c']);

        $this->postJson(route('accounting.quotations.email', $quotation))
            ->assertStatus(422)
            ->assertJsonPath('error', 'no_recipient');

        $this->assertSame(0, QuotationEmailJob::count());
        Bus::assertNothingDispatched();
    }

    public function test_email_endpoints_require_the_send_email_permission(): void
    {
        Bus::fake();

        $quotation = $this->sentQuotation();

        $viewer = \App\Models\User::factory()->create();
        $viewer->companies()->attach($this->company->id, ['role' => 'auditor']);
        setPermissionsTeamId($this->company->id);
        $viewer->assignRole('auditor');
        $viewer->update(['current_company_id' => $this->company->id]);

        $this->actingAs($viewer)->postJson(route('accounting.quotations.email', $quotation))->assertForbidden();
        $this->actingAs($viewer)->postJson(route('accounting.quotations.email-retry', $quotation))->assertForbidden();

        $this->assertSame(0, QuotationEmailJob::count());
    }

    /* ------------------------------------------------------------------
     * §4.5 — retry carries the attempt lineage
     * ------------------------------------------------------------------ */

    public function test_retry_endpoint_increments_the_attempt_counter(): void
    {
        Bus::fake();

        $quotation = $this->sentQuotation();

        $this->postJson(route('accounting.quotations.email', $quotation))->assertStatus(202);
        $this->postJson(route('accounting.quotations.email-retry', $quotation))
            ->assertStatus(202)
            ->assertJsonPath('attempts', 2);

        $this->assertSame(2, QuotationEmailJob::count());
        $this->assertSame(2, (int) QuotationEmailJob::where('quotable_id', $quotation->id)->max('attempts'));
    }

    public function test_retry_refuses_a_draft_like_the_first_send(): void
    {
        Bus::fake();

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $this->postJson(route('accounting.quotations.email-retry', $quotation))
            ->assertStatus(422)
            ->assertJsonPath('error', 'draft');
    }

    /* ------------------------------------------------------------------
     * §4.4 — cancel is recorded on the job only
     * ------------------------------------------------------------------ */

    public function test_cancel_endpoint_flags_the_job_and_derives_a_cancelled_status(): void
    {
        $quotation = $this->sentQuotation();

        $job = app(QuotationEmailDispatchService::class)
            ->start($quotation, 'billing@acme.test', $this->user->id)
            ->forceFill(['status' => QuotationEmailJob::STATUS_RUNNING])
            ->fresh();

        $response = $this->postJson(route('accounting.email-jobs.cancel', $job->id));

        $response->assertOk()->assertJsonPath('status', 'cancelled');

        $fresh = $job->fresh();
        $this->assertNotNull($fresh->cancel_requested_at);
        $this->assertTrue($fresh->isCancelled());
        $this->assertSame('cancelled', $fresh->clientStatus());

        // §4.4 — the quotation is deliberately left untouched.
        $this->assertNull($quotation->fresh()->email_status);
        $this->assertNull($quotation->fresh()->emailed_at);
    }

    public function test_status_endpoint_reports_progress_then_completion(): void
    {
        Mail::fake();
        // The pipeline is dispatched after the response, which the test kernel
        // also terminates — fake the bus so the job cannot finish underneath
        // the stage transitions this test is asserting.
        Bus::fake();

        $quotation = $this->sentQuotation();

        $job = app(QuotationEmailDispatchService::class)->start($quotation, 'billing@acme.test', $this->user->id);

        $this->getJson(route('accounting.email-jobs.show', $job->id))
            ->assertOk()
            ->assertJsonPath('status', QuotationEmailJob::STATUS_RUNNING)
            ->assertJsonPath('stage', 'preparing')
            ->assertJsonPath('percent', 22);

        app(QuotationEmailDispatchService::class)->stage($job, 'delivering');

        $this->getJson(route('accounting.email-jobs.show', $job->id))
            ->assertOk()
            ->assertJsonPath('stage', 'delivering')
            ->assertJsonPath('stage_label', 'Delivering to recipient mailbox')
            ->assertJsonPath('percent', 76);

        app(QuotationEmailDispatchService::class)->succeed($job);

        $this->getJson(route('accounting.email-jobs.show', $job->id))
            ->assertOk()
            ->assertJsonPath('status', QuotationEmailJob::STATUS_SUCCEEDED)
            ->assertJsonPath('percent', 100);
    }

    public function test_status_and_cancel_endpoints_are_tenant_scoped(): void
    {
        Mail::fake();
        Bus::fake();

        $quotation = $this->sentQuotation();
        $job = app(QuotationEmailDispatchService::class)->start($quotation, 'billing@acme.test', $this->user->id);

        // A second company that this session has no business touching.
        $other = Company::create(['name' => 'Other Co', 'company_code' => 'OTH', 'is_active' => true]);
        $job->forceFill(['company_id' => $other->id])->save();

        $this->getJson(route('accounting.email-jobs.show', $job->id))->assertForbidden();
        $this->postJson(route('accounting.email-jobs.cancel', $job->id))->assertForbidden();

        $job->forceFill(['company_id' => $this->company->id])->save();
        $this->getJson(route('accounting.email-jobs.show', 999999))->assertNotFound();
    }

    /* ------------------------------------------------------------------
     * §3 — the four-stage pipeline
     * ------------------------------------------------------------------ */

    public function test_pipeline_records_all_four_stages_then_succeeds(): void
    {
        Mail::fake();
        Bus::fake();

        $quotation = $this->sentQuotation();
        $job = app(QuotationEmailDispatchService::class)->start($quotation, 'billing@acme.test', $this->user->id);

        // Capture the stage trail by replaying the job against a recorder that
        // appends each stage before delegating to the real service.
        $dispatch = new class extends QuotationEmailDispatchService
        {
            public array $trail = [];

            public function stage(\App\Models\QuotationEmailJob $job, string $stage): \App\Models\QuotationEmailJob
            {
                $this->trail[] = $stage;

                return parent::stage($job, $stage);
            }
        };

        (new SendQuotationEmail($job->id, (int) $this->company->id))->handle($dispatch);

        $this->assertSame(
            ['preparing', 'uploading', 'delivering', 'recording'],
            $dispatch->trail,
            'The pipeline must emit exactly the four §3 stages, in order.'
        );

        $fresh = $job->fresh();
        $this->assertSame(QuotationEmailJob::STATUS_SUCCEEDED, $fresh->status);
        $this->assertSame(100, $fresh->percent());

        $quotation->refresh();
        $this->assertSame('sent', $quotation->email_status);
        $this->assertSame('billing@acme.test', $quotation->emailed_to);
        $this->assertNotNull($quotation->emailed_at);
        $this->assertNull($quotation->fail_reason);
        $this->assertTrue($quotation->hasEmailed());
        $this->assertFalse($quotation->emailFailed());

        // QuotationMail is ShouldQueue, so the transport hand-off is asserted as
        // a queued mail rather than a sent one.
        Mail::assertQueued(\App\Mail\QuotationMail::class);
    }

    public function test_pipeline_failure_records_the_reason_and_retracts_the_stamp(): void
    {
        $quotation = $this->sentQuotation();

        // A previous successful dispatch the failure must retract.
        $quotation->forceFill([
            'emailed_at' => now()->subDay(),
            'emailed_to' => 'old@acme.test',
            'email_status' => 'sent',
        ])->save();

        $job = QuotationEmailJob::create([
            'company_id' => $this->company->id,
            'quotable_type' => Quotation::class,
            'quotable_id' => $quotation->id,
            'recipient' => 'billing@acme.test',
            'stage' => 'preparing',
            'status' => QuotationEmailJob::STATUS_RUNNING,
            'attempts' => 2,
            'dispatched_by' => $this->user->id,
        ]);

        app(QuotationEmailDispatchService::class)->fail($job, new \RuntimeException('550 5.1.1 Mailbox unavailable'));

        $fresh = $job->fresh();
        $this->assertSame(QuotationEmailJob::STATUS_FAILED, $fresh->status);
        $this->assertSame('550', $fresh->error_code);
        $this->assertStringContainsString('Mailbox unavailable', $fresh->error_message);

        $quotation->refresh();
        // §4.3 — an undelivered quotation must never still look sent.
        $this->assertSame('failed', $quotation->email_status);
        $this->assertNull($quotation->emailed_at);
        $this->assertNull($quotation->emailed_to);
        $this->assertStringContainsString('Mailbox unavailable', $quotation->fail_reason);
        $this->assertTrue($quotation->emailFailed());
    }

    public function test_pipeline_abandons_a_cancelled_dispatch_without_sending(): void
    {
        Mail::fake();
        Bus::fake();

        $quotation = $this->sentQuotation();

        $job = app(QuotationEmailDispatchService::class)->start($quotation, 'billing@acme.test', $this->user->id);
        app(QuotationEmailDispatchService::class)->cancel($job);

        (new SendQuotationEmail($job->id, (int) $this->company->id))
            ->handle(app(QuotationEmailDispatchService::class));

        $this->assertSame(QuotationEmailJob::STATUS_RUNNING, $job->fresh()->status);
        $this->assertNull($quotation->fresh()->email_status);
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }

    public function test_reason_for_extracts_the_smtp_code_and_truncates(): void
    {
        $dispatch = app(QuotationEmailDispatchService::class);

        [$code, $message] = $dispatch->reasonFor(new \RuntimeException('Connection could not be established'));
        $this->assertNull($code);
        $this->assertSame('Connection could not be established', $message);

        [$code, $message] = $dispatch->reasonFor(new \RuntimeException('550 5.1.1 Recipient rejected'));
        $this->assertSame('550', $code);
        $this->assertSame('550 — 550 5.1.1 Recipient rejected', $message);

        [, $message] = $dispatch->reasonFor(new \RuntimeException(str_repeat('x', 400)));
        $this->assertLessThanOrEqual(255, mb_strlen($message));
    }

    /* ------------------------------------------------------------------
     * §2.1 — durable state + audit lineage
     * ------------------------------------------------------------------ */

    public function test_success_writes_the_emailed_audit_the_register_rail_reads(): void
    {
        $quotation = $this->sentQuotation();
        $job = QuotationEmailJob::create([
            'company_id' => $this->company->id,
            'quotable_type' => Quotation::class,
            'quotable_id' => $quotation->id,
            'recipient' => 'billing@acme.test',
            'stage' => 'preparing',
            'status' => QuotationEmailJob::STATUS_RUNNING,
            'attempts' => 1,
            'dispatched_by' => $this->user->id,
        ]);

        app(QuotationEmailDispatchService::class)->succeed($job);

        $audit = AuditLog::where('auditable_type', Quotation::class)
            ->where('auditable_id', $quotation->id)
            ->where('action', 'emailed')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'A successful dispatch must audit the `emailed` action.');
        $this->assertSame('billing@acme.test', $audit->new_values['emailed_to']);
        $this->assertSame('sent', $audit->new_values['email_status']);
    }

    public function test_failure_audit_supersedes_an_earlier_success(): void
    {
        $quotation = $this->sentQuotation();
        $dispatch = app(QuotationEmailDispatchService::class);

        $first = QuotationEmailJob::create([
            'company_id' => $this->company->id,
            'quotable_type' => Quotation::class,
            'quotable_id' => $quotation->id,
            'recipient' => 'billing@acme.test',
            'stage' => 'recording',
            'status' => QuotationEmailJob::STATUS_RUNNING,
            'attempts' => 1,
            'dispatched_by' => $this->user->id,
        ]);
        $dispatch->succeed($first);

        $second = QuotationEmailJob::create([
            'company_id' => $this->company->id,
            'quotable_type' => Quotation::class,
            'quotable_id' => $quotation->id,
            'recipient' => 'billing@acme.test',
            'stage' => 'uploading',
            'status' => QuotationEmailJob::STATUS_RUNNING,
            'attempts' => 2,
            'dispatched_by' => $this->user->id,
        ]);
        $dispatch->fail($second, new \RuntimeException('421 Service temporarily unavailable'));

        $latest = AuditLog::where('auditable_type', Quotation::class)
            ->where('auditable_id', $quotation->id)
            ->orderByDesc('id')
            ->first();

        $this->assertSame('email_failed', $latest->action);

        // The register's needs-attention rail reads the LATEST audit action, so
        // the chip/rail must now report the failure, not the earlier success.
        // The rail itself stays a plain no-JS form posting to the legacy endpoint
        // (the retry endpoint answers JSON), so it is the legacy route that is
        // asserted here; the workspace uses the retry route.
        $this->get(route('accounting.quotations.index'))
            ->assertOk()
            ->assertSee('q2-attn-fail', false)
            ->assertSee(route('accounting.quotations.email', $quotation), false);
    }

    public function test_register_chip_and_search_surface_a_failed_dispatch(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->forceFill(['email_status' => 'failed', 'fail_reason' => '550 — Mailbox unavailable'])->save();

        // The chip is rendered server-side into the row.
        $this->get(route('accounting.quotations.index'))
            ->assertOk()
            ->assertSee('q2-emchip', false)
            ->assertSee('Email failed', false);

        // §3.1 — "email failed" and the recorded address are both searchable.
        $this->get(route('accounting.quotations.index', ['search' => 'email failed']))
            ->assertOk()
            ->assertSee($quotation->quotation_number, false);

        $this->get(route('accounting.quotations.index', ['search' => 'billing@acme.test']))
            ->assertOk()
            ->assertSee($quotation->quotation_number, false);
    }

    public function test_row_chip_is_rendered_in_the_status_cell_not_the_actions_cell(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->forceFill([
            'email_status' => 'failed',
            'emailed_to' => 'billing@acme.test',
            'fail_reason' => '550 — Mailbox unavailable',
        ])->save();

        $html = $this->get(route('accounting.quotations.index'))
            ->assertOk()
            ->getContent();

        // The client-side patchRowChip() resolves the cell with this same
        // class, so the server-rendered chip must sit inside it too. Index 5
        // is Status; index 6 is Actions (the icon-button group).
        $this->assertSame(1, preg_match('#<tr[^>]*data-qid="' . $quotation->id . '".*?</tr>#s', $html, $m));
        $row = $m[0];
        $this->assertSame(1, preg_match('#<td class="q2-statcell">.*?<span class="q2-badge#s', $row));
        $this->assertSame(1, preg_match('#<td class="q2-statcell">(?:(?!</td>).)*q2-emchip#s', $row));
        $this->assertSame(0, preg_match('#<td class="q2-right">(?:(?!</td>).)*q2-emchip#s', $row));
    }

    public function test_workspace_exposes_the_dispatch_rows_and_actions(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->forceFill([
            'email_status' => 'failed',
            'emailed_to' => 'billing@acme.test',
            'fail_reason' => '550 — Mailbox unavailable',
        ])->save();

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Email dispatch', $html);
        $this->assertStringContainsString('qv-disp-status', $html);
        $this->assertStringContainsString('qv-disp-to', $html);
        $this->assertStringContainsString('qv-disp-reason', $html);
        $this->assertStringContainsString('Re-send email', $html);

        // The retry target rides in the window.QUOT_VIEW payload (Json::from
        // escapes its slashes), so decode the payload and assert the resolved
        // contract instead of grepping the raw URL out of the escaped JSON.
        $this->assertSame(1, preg_match('/window\.QUOT_VIEW = (\{.*?\});/s', $html, $m));
        $payload = json_decode($m[1], true);
        $this->assertIsArray($payload);
        $this->assertSame(
            route('accounting.quotations.email-retry', $quotation),
            $payload[(string) $quotation->id]['urls']['email_retry'],
        );

        // No status URL is exposed per row: an in-flight poll resumes from the
        // dock's sessionStorage copy, so the payload carries only the two
        // dispatch targets the workspace can act on.
        $this->assertArrayHasKey('email', $payload[(string) $quotation->id]['urls']);
        $this->assertArrayNotHasKey('email_job', $payload[(string) $quotation->id]['urls']);
        $this->assertStringContainsString('d.urls.email_retry', $html);
    }

    /* ------------------------------------------------------------------
     * §3.2 / §4.3 — the modal markup the client renders from
     * ------------------------------------------------------------------ */

    public function test_register_renders_the_dispatch_modal_and_dock(): void
    {
        $this->sentQuotation();

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        // The partial is self-contained: its own scrim, stages, dock and copy.
        $this->assertStringContainsString('id="qd-scrim"', $html);
        $this->assertStringContainsString('role="alertdialog"', $html);
        foreach (QuotationEmailJob::STAGE_LABELS as $label) {
            $this->assertStringContainsString($label, $html);
        }

        // §4.4 cancel + §3.2 minimize.
        $this->assertStringContainsString('Cancel &amp; close', $html);
        // Matched without the em dash so the assertion does not depend on the
        // punctuation glyph the copy spec renders.
        $this->assertStringContainsString('Minimize to background', $html);

        // §4.3 retry + dock copy.
        $this->assertStringContainsString('Try again', $html);
        $this->assertStringContainsString('Running in background if minimized', $html);

        // Success + failure copy.
        $this->assertStringContainsString('Quotation delivered', $html);
        $this->assertStringContainsString('Delivery failed', $html);
        $this->assertStringContainsString('Email delivered in background', $html);

        // No auto-close timers anywhere in the dispatch partial.
        $this->assertStringNotContainsString('setTimeout(clos', $html);
    }

    public function test_register_forbids_blurred_surfaces(): void
    {
        $this->sentQuotation();

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        // The scrim is a SOLID tint — a blurred backdrop both looks wrong over a
        // dense register and repaints the whole page every frame.
        $this->assertStringNotContainsString('backdrop-filter', $html);
    }

    public function test_dispatch_copy_is_the_agreed_wording(): void
    {
        $this->sentQuotation();

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        foreach ([
            'Preparing PDF document',
            'Uploading to mail server',
            'Delivering to recipient mailbox',
            'Recording email dispatch audit',
            'Delivery failed',
            'the quotation is unchanged',
            'Email could not be delivered',
            'Email delivered in background',
            'View quotation',
            'Dismiss',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }
}