<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Mail\QuotationMail;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\Accounting\QuotationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

class QuotationFormTest extends TestCase
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
            'name' => 'Quotation Test Co',
            'company_code' => 'QTC',
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

    public function test_create_renders_mockup_markup(): void
    {
        $this->get(route('accounting.quotations.create'))
            ->assertOk()
            ->assertSee('Create Quotation', false)
            ->assertSee('Summary')
            ->assertSee('Add Line')
            ->assertSee('q2-optcard', false)
            ->assertSee('Provide customer email to activate this button', false)
            ->assertDontSee('q2-ocr', false)
            ->assertSee('customer_type', false)
            ->assertSee('notes_internal', false)
            ->assertSee('valid_until')
            ->assertSee('Internal Notes')
            ->assertSee('quotation-form', false);
    }

    public function test_success_dialog_renders_for_flashed_success_payload(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $response = $this->withSession([
            'quotation_success' => [
                'title' => 'Quotation finalized',
                'ref' => $quotation->quotation_number,
                'name' => 'Acme Retail Ltd',
                'total' => 'MWK 337.85',
                'status' => 'sent',
                'print' => true,
                'send' => false,
                'email' => null,
                'walkin' => false,
                'view_url' => route('accounting.quotations.show', $quotation),
                'print_url' => route('accounting.quotations.print', $quotation),
            ],
        ])->get(route('accounting.quotations.show', $quotation));

        $response->assertOk()
            ->assertSee('quot-ok-scrim', false)
            ->assertSee('Quotation finalized')
            ->assertSee('View quotation')
            ->assertSee('Open print preview');
    }

    public function test_edit_renders_existing_lines(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $this->get(route('accounting.quotations.edit', $quotation))
            ->assertOk()
            ->assertSee('Edit Quotation', false)
            ->assertSee($quotation->quotation_number, false)
            ->assertSee('Ergonomic Chair')
            ->assertSee('Breakdown')
            ->assertSee('Save Changes', false);
    }

    public function test_index_renders_view_modal_and_payload(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $response = $this->get(route('accounting.quotations.index'));

        $response->assertOk()
            ->assertSee('qv-scrim', false)
            ->assertSee('q2-mbox--view', false)
            ->assertSee('qv-steps', false)
            ->assertSee('qv-foot', false)
            ->assertSee('window.QUOT_VIEW', false)
            ->assertSee($quotation->quotation_number, false);

        $content = $response->getContent();
        $this->assertStringContainsString('"status":"draft"', $content);
        $this->assertStringContainsString('Acme Retail Ltd', $content);
        $this->assertStringContainsString(
            str_replace('/', '\\/', route('accounting.quotations.edit', $quotation)),
            $content
        );
    }

    public function test_index_nests_no_interactive_element_inside_another(): void
    {
        // A stray </a> closing the View <button> silently nested the locked
        // edit icon INSIDE the data-qview trigger, so clicking a locked control
        // opened the View modal (R9). Guard the whole table's tag balance.
        app(QuotationService::class)->create($this->payload(), $this->user->id);
        app(QuotationService::class)->create($this->payload(['status' => Quotation::STATUS_SENT]), $this->user->id);

        $response = $this->get(route('accounting.quotations.index'));
        $response->assertOk();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $this->assertSame(0, $xpath->query('//button//button | //button//a | //a//button | //a//a')->length,
            'The register must not nest one interactive element inside another.');
        $this->assertSame(0, $xpath->query('//*[@data-qview]//*[contains(concat(" ", normalize-space(@class), " "), " is-locked ")]')->length,
            'A locked control must not live inside a data-qview trigger (R9).');
        $this->assertGreaterThan(0, $xpath->query('//span[contains(concat(" ", normalize-space(@class), " "), " is-locked ")]')->length,
            'A non-draft row must render the locked edit icon.');
    }

    public function test_index_rq_mode_default_is_finalize(): void
    {
        // R13 · "Finalize & issue" is the default; rqApplyMode() keeps the
        // hidden POSTed mode in step so a draft selection can't finalize.
        $response = $this->get(route('accounting.quotations.index'));

        $response->assertOk()
            ->assertSee('<input type="hidden" name="mode" id="rq-mode" value="finalize">', false)
            ->assertSee('id="rmFinal" value="finalize" checked', false)
            ->assertSee("mode.value = draft ? 'draft' : 'finalize'", false);
    }

    public function test_send_and_email_routes_require_edit_permission(): void
    {
        // A view-only role (auditor) must not be able to mark a quotation sent
        // or push it to a customer's inbox, even by posting the route directly.
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        app(QuotationService::class)->send($quotation);

        $viewer = \App\Models\User::factory()->create();
        $viewer->companies()->attach($this->company->id, ['role' => 'auditor']);
        setPermissionsTeamId($this->company->id);
        $viewer->assignRole('auditor');
        $viewer->update(['current_company_id' => $this->company->id]);

        $this->actingAs($viewer)
            ->post(route('accounting.quotations.send', $quotation))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('accounting.quotations.email', $quotation))
            ->assertForbidden();

        \Illuminate\Support\Facades\Mail::fake();
        $this->assertSame(Quotation::STATUS_SENT, $quotation->fresh()->status);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_stale_transition_post_flashes_an_error_instead_of_a_500(): void
    {
        // A stale page (or a direct POST) can ask for a transition the current
        // status refuses. The service message must reach the user as a flash.
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        app(QuotationService::class)->send($quotation);
        app(QuotationService::class)->accept($quotation);

        $response = $this->post(route('accounting.quotations.send', $quotation));

        $response->assertRedirect(route('accounting.quotations.show', $quotation))
            ->assertSessionHas('error');
        $this->assertSame(Quotation::STATUS_ACCEPTED, $quotation->fresh()->status);
    }

    public function test_void_quotation_cannot_be_emailed(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        app(QuotationService::class)->send($quotation);
        app(QuotationService::class)->void($quotation, 'Withdrawn by customer', $this->user->id);

        $this->post(route('accounting.quotations.email', $quotation))
            ->assertRedirect(route('accounting.quotations.show', $quotation))
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\Mail::assertNothingSent();
        $this->assertNull($quotation->fresh()->emailed_at);
    }

    public function test_disabled_email_template_is_not_used(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        app(QuotationService::class)->send($quotation);

        EmailTemplate::create([
            'company_id' => $this->company->id,
            'event_type' => 'quotation_sent',
            'subject' => 'DISABLED {{quotation_number}}',
            'body' => 'DISABLED BODY',
            'is_enabled' => false,
        ]);

        $this->post(route('accounting.quotations.email', $quotation))->assertRedirect();

        \Illuminate\Support\Facades\Mail::assertQueued(QuotationMail::class, function (QuotationMail $mail) {
            $envelope = $mail->envelope();

            return ! str_contains($envelope->subject, 'DISABLED');
        });
    }

    public function test_store_persists_quote_fields_and_lines(): void
    {
        $response = $this->post(route('accounting.quotations.store'), $this->payload());

        $response->assertRedirect();

        $quotation = Quotation::where('company_id', $this->company->id)->first();
        $this->assertNotNull($quotation);
        $this->assertSame('MWK', $quotation->currency);
        $this->assertSame('RFQ-009', $quotation->reference);
        $this->assertSame('Please include delivery.', $quotation->memo);
        $this->assertSame('draft', $quotation->status);
        $this->assertSame($this->customer->id, $quotation->customer_id);
        $this->assertCount(1, $quotation->lines);

        $line = $quotation->lines->first();
        $this->assertSame($this->product->id, $line->product_id);
        $this->assertEquals(2, $line->quantity);
        $this->assertEquals(150, $line->unit_price);
        $this->assertEquals(16.5, $line->tax_rate);
        $this->assertEquals(290.00, (float) $line->amount);
        $this->assertEquals(47.85, (float) $line->tax_amount);
        $this->assertEquals(337.85, (float) $line->line_total);

        $this->assertEquals(290.00, (float) $quotation->amount);
        $this->assertEquals(47.85, (float) $quotation->tax_total);
        $this->assertEquals(337.85, (float) $quotation->total);
    }

    public function test_store_save_later_creates_draft_with_success_dialog(): void
    {
        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'submission_mode' => 'finalize_later',
        ]));

        $quotation = Quotation::where('company_id', $this->company->id)->firstOrFail();
        $response->assertRedirect(route('accounting.quotations.show', $quotation));
        $response->assertSessionHas('quotation_success');
        $this->assertSame('draft', $quotation->status);

        $success = session('quotation_success');
        $this->assertSame('Quotation saved', $success['title']);
        $this->assertFalse($success['print']);
        $this->assertFalse($success['send']);
    }

    public function test_store_finalize_print_shows_success_dialog_with_print_action(): void
    {
        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'submission_mode' => 'finalize_print',
        ]));

        $quotation = Quotation::where('company_id', $this->company->id)->firstOrFail();
        $response->assertRedirect(route('accounting.quotations.show', $quotation));
        $response->assertSessionHas('quotation_success');
        $this->assertSame('sent', $quotation->status);

        $success = session('quotation_success');
        $this->assertSame('Quotation finalized', $success['title']);
        $this->assertTrue($success['print']);
        $this->assertFalse($success['send']);
    }

    public function test_store_finalize_send_emails_and_shows_success_dialog(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'submission_mode' => 'finalize_send',
        ]));

        $quotation = Quotation::where('company_id', $this->company->id)->firstOrFail();
        $response->assertRedirect(route('accounting.quotations.show', $quotation));
        $response->assertSessionHas('quotation_success');
        $this->assertSame('sent', $quotation->status);

        $success = session('quotation_success');
        $this->assertSame('Quotation finalized & sent', $success['title']);
        $this->assertTrue($success['print']);
        $this->assertTrue($success['send']);
        $this->assertSame('billing@acme.test', $success['email']);
    }

    public function test_store_finalize_send_requires_valid_email(): void
    {
        $response = $this->from(route('accounting.quotations.create'))
            ->post(route('accounting.quotations.store'), $this->payload([
                'submission_mode' => 'finalize_send',
                'email' => 'not-an-email',
            ]));

        $response->assertRedirect(route('accounting.quotations.create'));
        $response->assertSessionHasErrors('email');
    }

    public function test_store_save_draft_redirects_to_show(): void
    {
        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'submission_mode' => 'draft',
        ]));

        $quotation = Quotation::where('company_id', $this->company->id)->firstOrFail();
        $response->assertRedirect(route('accounting.quotations.show', $quotation));
        $this->assertSame('draft', $quotation->status);
    }

    public function test_store_rejects_missing_income_account(): void
    {
        $payload = $this->payload();
        $payload['lines'][0]['income_account_id'] = null;

        $response = $this->from(route('accounting.quotations.create'))
            ->post(route('accounting.quotations.store'), $payload);

        $response->assertSessionHasErrors('lines.0.income_account_id');
        $this->assertDatabaseCount('quotations', 0);
    }

    /**
     * R16 Â· DO-NOT-REINTRODUCE: attachments section. The UI carries no attachment
     * control, and the show page exposes no attachments tab or card.
     */
    public function test_no_attachment_ui_is_exposed(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $this->get(route('accounting.quotations.create'))
            ->assertOk()
            ->assertDontSee('name="files"', false)
            ->assertDontSee('Attachments');

        $this->get(route('accounting.quotations.edit', $quotation))
            ->assertOk()
            ->assertDontSee('name="files"', false)
            ->assertDontSee('Attachments');

        $this->get(route('accounting.quotations.show', $quotation))
            ->assertOk()
            ->assertDontSee('Attachments')
            ->assertDontSee('No attachments for this quotation.');

        $this->get(route('accounting.quotations.index'))
            ->assertOk()
            ->assertDontSee('Attachments');
    }

    public function test_update_persists_changes_and_currency(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $response = $this->put(route('accounting.quotations.update', $quotation), $this->payload([
            'currency' => 'USD',
            'reference' => 'RFQ-010',
            'lines' => [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Ergonomic Chair',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'discount' => 0,
                    'tax_rate' => 0,
                    'income_account_id' => $this->revenueAccount->id,
                ],
            ],
        ]));

        $response->assertRedirect();

        $quotation->refresh();
        $this->assertSame('USD', $quotation->currency);
        $this->assertSame('RFQ-010', $quotation->reference);
        $this->assertCount(1, $quotation->lines);
        $this->assertEquals(200.00, (float) $quotation->amount);
        $this->assertEquals(200.00, (float) $quotation->total);
    }

    public function test_update_rejects_non_draft(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        $quotation->update(['status' => Quotation::STATUS_SENT]);

        $this->put(route('accounting.quotations.update', $quotation), $this->payload())
            ->assertRedirect(route('accounting.quotations.show', $quotation));
    }

    public function test_destroy_deletes_draft_quotation(): void
    {
        $creator = \App\Models\User::factory()->create();
        $quotation = app(QuotationService::class)->create($this->payload(), $creator->id);

        $response = $this->delete(route('accounting.quotations.destroy', $quotation));

        $response->assertRedirect(route('accounting.quotations.index'));
        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
    }

    public function test_destroy_rejects_non_draft(): void
    {
        $creator = \App\Models\User::factory()->create();
        $quotation = app(QuotationService::class)->create($this->payload(), $creator->id);
        $quotation->update(['status' => Quotation::STATUS_SENT]);

        $this->delete(route('accounting.quotations.destroy', $quotation))
            ->assertRedirect(route('accounting.quotations.show', $quotation))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
    }

    public function test_index_renders_edit_and_requote_modals(): void
    {
        app(QuotationService::class)->create($this->payload(), $this->user->id);

        $this->get(route('accounting.quotations.index'))
            ->assertOk()
            ->assertSee('eq-scrim', false)
            ->assertSee('rq-scrim', false)
            ->assertSee('q2-mbox--edit', false)
            ->assertSee('eq-form', false)
            ->assertSee('rq-form', false)
            ->assertSee('q2-ltable--edit', false)
            ->assertSee('data-rqsubmit="draft"', false)
            ->assertSee('data-rqsubmit="send"', false)
            ->assertSee('rq-final-btn', false)
            ->assertSee('rq-send-item', false)
            ->assertSee('q2-hint-left', false)
            ->assertSee('quotOpenEdit', false)
            ->assertSee('quotOpenRequote', false)
            ->assertSee('window.QUOT_DEFAULT_ACCOUNT', false)
            ->assertSee('window.QUOT_UPDATE_URL', false)
            ->assertSee('Finalize & create quotation')
            ->assertSee('Finalize & send to customer')
            ->assertSee('Provide customer email to activate this button');
    }

    public function test_view_payload_carries_line_discount_tax_and_account(): void
    {
        app(QuotationService::class)->create($this->payload([
            'lines' => [[
                'product_id' => $this->product->id,
                'description' => 'Ergonomic Chair',
                'quantity' => 2,
                'unit_price' => 150,
                'disc_pct' => 10,
                'tax_rate' => 16.5,
                'income_account_id' => $this->revenueAccount->id,
            ]],
        ]), $this->user->id);

        $content = $this->get(route('accounting.quotations.index'))->getContent();

        $this->assertStringContainsString('"disc":10', $content);
        $this->assertStringContainsString('"tax":16.5', $content);
        $this->assertStringContainsString('"account":' . $this->revenueAccount->id, $content);
        $this->assertStringContainsString('"customer_id":' . $this->customer->id, $content);
        $this->assertStringContainsString('"reference":"RFQ-009"', $content);
        $this->assertStringContainsString('"currency":"MWK"', $content);
    }

    public function test_requote_creates_new_draft_and_supersedes_source(): void
    {
        $creator = \App\Models\User::factory()->create();
        $source = app(QuotationService::class)->create($this->payload(), $creator->id);

        $response = $this->post(route('accounting.quotations.requote', $source), [
            'customer_id' => $this->customer->id,
            'customer_name' => 'Acme Retail Ltd',
            'quotation_date' => '2026-03-01',
            'validity_days' => 30,
            'mode' => 'draft',
            'lines' => [[
                'description' => 'Ergonomic Chair',
                'quantity' => 3,
                'unit_price' => 150,
                'disc_pct' => 0,
                'tax_rate' => 16.5,
                'income_account_id' => $this->revenueAccount->id,
            ]],
        ]);

        $new = Quotation::where('id', '!=', $source->id)->firstOrFail();

        $response->assertRedirect(route('accounting.quotations.show', $new));
        $this->assertSame('draft', $new->status);
        $this->assertSame('2026-03-31', $new->valid_until->toDateString());
        $this->assertSame($this->customer->id, $new->customer_id);
        $this->assertEquals(3, $new->lines->first()->quantity);
        $this->assertSame($new->id, $source->fresh()->superseded_by);
    }

    public function test_requote_finalize_marks_sent_and_carries_lines(): void
    {
        $creator = \App\Models\User::factory()->create();
        $source = app(QuotationService::class)->create($this->payload(), $creator->id);

        $response = $this->post(route('accounting.quotations.requote', $source), [
            'customer_id' => $this->customer->id,
            'customer_name' => 'Acme Retail Ltd',
            'quotation_date' => '2026-03-01',
            'validity_days' => 14,
            'mode' => 'finalize',
        ]);

        $new = Quotation::where('id', '!=', $source->id)->firstOrFail();
        $response->assertRedirect(route('accounting.quotations.show', $new));
        $response->assertSessionHas('quotation_success');
        $this->assertSame('sent', $new->status);
        $this->assertCount(1, $new->lines);
        $this->assertEquals(2, $new->lines->first()->quantity);

        $success = session('quotation_success');
        $this->assertSame('Quotation finalized', $success['title']);
        $this->assertTrue($success['print']);
        $this->assertFalse($success['send']);
    }

    public function test_requote_send_emails_and_shows_success_dialog(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $creator = \App\Models\User::factory()->create();
        $source = app(QuotationService::class)->create($this->payload(), $creator->id);

        $response = $this->post(route('accounting.quotations.requote', $source), [
            'customer_id' => $this->customer->id,
            'customer_name' => 'Acme Retail Ltd',
            'email' => 'billing@acme.test',
            'quotation_date' => '2026-03-01',
            'validity_days' => 14,
            'mode' => 'finalize',
            'send' => 1,
        ]);

        $new = Quotation::where('id', '!=', $source->id)->firstOrFail();
        $response->assertRedirect(route('accounting.quotations.show', $new));
        $response->assertSessionHas('quotation_success');
        $this->assertSame('sent', $new->status);

        $success = session('quotation_success');
        $this->assertSame('Quotation finalized & sent', $success['title']);
        $this->assertTrue($success['send']);
    }

    public function test_requote_with_changed_customer_name_creates_walkin_customer(): void
    {
        $creator = \App\Models\User::factory()->create();
        $source = app(QuotationService::class)->create($this->payload(), $creator->id);

        $this->post(route('accounting.quotations.requote', $source), [
            'customer_id' => $this->customer->id,
            'customer_name' => 'Brand New Buyer Ltd',
            'quotation_date' => '2026-03-01',
            'validity_days' => 14,
            'mode' => 'draft',
        ])->assertRedirect();

        $new = Quotation::where('id', '!=', $source->id)->firstOrFail();
        $this->assertSame('Brand New Buyer Ltd', $new->customer_name);
        $walkin = \App\Models\Customer::where('company_id', $this->company->id)
            ->where('name', 'Brand New Buyer Ltd')
            ->firstOrFail();
        $this->assertNotSame($this->customer->id, $walkin->id);
        $this->assertSame($walkin->id, $new->customer_id);
    }

    /* ---------- Â§G print preview ---------- */

    public function test_index_renders_print_preview_overlay(): void
    {
        app(QuotationService::class)->create($this->payload(), $this->user->id);

        $content = $this->get(route('accounting.quotations.index'))
            ->assertOk()
            ->assertSee('class="pview"', false)
            ->assertSee('id="printView"', false)
            ->assertSee('class="ptoolbar"', false)
            ->assertSee('id="pvBack"', false)
            ->assertSee('id="pvPdf"', false)
            ->assertSee('id="pvPrint"', false)
            ->assertSee('id="qSheet"', false)
            ->assertSee('id="qRef"', false)
            ->assertSee('id="qStamp"', false)
            ->assertSee('id="qPrinted"', false)
            ->assertSee('id="qmCust"', false)
            ->assertSee('id="qLines"', false)
            ->assertSee('id="qTotals"', false)
            ->assertSee('class="qpanels"', false)
            ->assertSee('class="v-foot"', false)
            ->assertSee('openQuotePrint', false)
            ->assertSee('PAGE 1 OF 1')
            ->assertSee(__('Customer Acceptance'))
            ->assertSee(__('Amount in words'))
            ->assertDontSee('Authorised by')
            ->assertDontSee('backdrop-filter')
            ->getContent();

        $this->assertMatchesRegularExpression('/function openQuotePrint\(id\)/', $content);
        $this->assertStringContainsString('quotPrintClose', $content);
    }

    public function test_view_payload_carries_print_fields(): void
    {
        app(QuotationService::class)->create($this->payload(), $this->user->id);

        $content = $this->get(route('accounting.quotations.index'))->getContent();
        $payload = json_decode(html_entity_decode($this->extractJsonObject($content, 'window.QUOT_VIEW = ')), true);

        $this->assertIsArray($payload);
        $row = reset($payload);
        $this->assertSame('Head Office', $row['branch']);
        $this->assertArrayHasKey('cc', $row);
        $this->assertSame($this->user->name, $row['prepared']);
        $this->assertSame('RFQ-009', $row['ref_no']);
        $this->assertArrayHasKey('currency_name', $row);
        $this->assertArrayHasKey('emailed_to', $row);
        $this->assertNotEmpty($row['words']);
        $this->assertSame(route('accounting.quotations.pdf', $row['id']), $row['urls']['pdf']);
    }

    public function test_print_route_renders_a4_sheet(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        $quotation->update(['status' => Quotation::STATUS_SENT]);

        $content = $this->get(route('accounting.quotations.print', $quotation->fresh()))
            ->assertOk()
            ->assertSee('class="sheet"', false)
            ->assertSee('class="v-head"', false)
            ->assertSee('class="meta"', false)
            ->assertSee('class="vlines"', false)
            ->assertSee('class="sigs"', false)
            ->assertSee('class="v-foot"', false)
            ->assertSee('PAGE 1 OF 1')
            ->assertSee(__('Customer Acceptance'))
            ->assertSee(__('Amount in words'))
            ->assertSee(__('Issued'))
            ->assertSee(route('accounting.quotations.pdf', $quotation->id), false)
            ->assertDontSee('Authorised')
            ->getContent();

        $this->assertStringNotContainsString('backdrop-filter', $content);
        $this->assertStringContainsString('@page', $content);
    }

    public function test_print_route_watermarks_draft(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $content = $this->get(route('accounting.quotations.print', $quotation->fresh()))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="wm"', $content);
        $this->assertStringContainsString('draftst', $content);
        $this->assertStringContainsString('>' . __('Draft') . '<', $content);
    }

    public function test_print_preview_words_match_grand_total(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $content = $this->get(route('accounting.quotations.print', $quotation->fresh()))->getContent();

        $this->assertStringContainsString(format_number($quotation->total), $content);
        $this->assertStringContainsString(
            e(amount_to_words(
                (float) $quotation->total,
                \App\Models\Currency::where('code', $quotation->currency)->value('name') ?: $quotation->currency
            )),
            $content
        );
    }

    /** Â§6.2 â€” the queued mail carries the PDF sheet and the company standard template. */
    public function test_quotation_mail_attaches_pdf_and_uses_company_template(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();

        \App\Models\EmailTemplate::create([
            'company_id' => $this->company->id,
            'event_type' => 'quotation_sent',
            'subject' => 'Quote {{quotation_number}} for {{customer_name}}',
            'body' => "Hi {{customer_name}}, see {{total_amount}}.",
            'is_enabled' => true,
        ]);

        $mail = new \App\Mail\QuotationMail($quotation);

        $this->assertSame(
            'Quote ' . $quotation->quotation_number . ' for Acme Retail Ltd',
            $mail->envelope()->subject
        );

        $body = $mail->content()->htmlString;
        $this->assertStringContainsString('Hi Acme Retail Ltd, see ', $body);
        $this->assertStringContainsString(format_number((float) $quotation->total), $body);
        $this->assertStringNotContainsString('@if', $body);

        $attachments = $mail->attachments();
        $this->assertCount(1, $attachments);

        $as = new \ReflectionProperty($attachments[0], 'as');
        $as->setAccessible(true);
        $this->assertSame('quotation-' . $quotation->quotation_number . '.pdf', $as->getValue($attachments[0]));

        $bytes = null;
        $attachments[0]->attachWith(
            fn ($path) => $path,
            function ($data) use (&$bytes) {
                $bytes = $data();

                return $data;
            }
        );
        $this->assertStringStartsWith('%PDF', (string) $bytes);
    }

    /** Â§6.3 â€” a final failure retracts the emailed stamp and leaves an audit trail. */
    public function test_failed_dispatch_clears_email_stamp_and_audits(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();
        app(QuotationService::class)->recordEmailed($quotation, 'billing@acme.test');

        $this->assertNotNull($quotation->fresh()->emailed_at);

        (new \App\Mail\QuotationMail($quotation))
            ->failed(new \RuntimeException('SMTP connection refused'));

        $quotation->refresh();
        $this->assertNull($quotation->emailed_at);
        $this->assertNull($quotation->emailed_to);

        $audit = \App\Models\AuditLog::where('auditable_type', Quotation::class)
            ->where('auditable_id', $quotation->id)
            ->where('action', 'email_failed')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('SMTP connection refused', $audit->notes);
    }

    /** Â§6.3 â€” the register surfaces the failure in the needs-attention rail. */
    public function test_register_surfaces_email_failure_with_confirm_gated_retry(): void
    {
        $quotation = $this->sentQuotation();
        (new \App\Mail\QuotationMail($quotation))
            ->failed(new \RuntimeException('Mailbox unavailable'));

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('q2-attn-fail', $html);
        $this->assertStringContainsString('Retry email', $html);
        $this->assertStringContainsString(route('accounting.quotations.email', $quotation), $html);
        $this->assertStringContainsString('fbConfirmSubmit', $html);
        $this->assertStringContainsString('name="return_to" value="register"', $html);
        $this->assertStringContainsString('Email failed', $html);

        // The confirm text must carry the recipient, never a raw %s placeholder.
        $this->assertStringContainsString('Retry emailing this quotation to billing@acme.test?', $html);
        $this->assertStringNotContainsString('%s', $html);
    }

    /** R15 â€” a draft can never carry a delivery failure into the rail. */
    public function test_register_rail_ignores_draft_email_failures(): void
    {
        $draft = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();
        (new \App\Mail\QuotationMail($draft))
            ->failed(new \RuntimeException('Mailbox unavailable'));

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('accounting.quotations.email', $draft), $html);
    }

    /** Â§R11 â€” a malformed recipient is never queued. */
    public function test_email_route_rejects_malformed_recipient(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->customer->update(['email' => 'jane@mail.c']);

        $quotation = $this->sentQuotation();

        $this->post(route('accounting.quotations.email', $quotation), ['return_to' => 'register'])
            ->assertRedirect(route('accounting.quotations.index'))
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\Mail::assertNothingQueued();
        $this->assertNull($quotation->fresh()->emailed_at);
    }

    /** The rail retry returns to the register and re-queues the dispatch. */
    public function test_email_route_retry_returns_to_register(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $quotation = $this->sentQuotation();

        $this->post(route('accounting.quotations.email', $quotation), ['return_to' => 'register'])
            ->assertRedirect(route('accounting.quotations.index'))
            ->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\QuotationMail::class);

        $this->assertSame('billing@acme.test', $quotation->fresh()->emailed_to);
        $this->assertNotNull($quotation->fresh()->emailed_at);
    }

    /** R15 â€” a draft can never be put in a customer's inbox. */
    public function test_email_route_refuses_draft_quotation(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();
        $this->assertSame(Quotation::STATUS_DRAFT, $quotation->status);

        $this->post(route('accounting.quotations.email', $quotation), ['return_to' => 'register'])
            ->assertRedirect(route('accounting.quotations.index'))
            ->assertSessionHas('error');

        \Illuminate\Support\Facades\Mail::assertNothingQueued();
        $this->assertNull($quotation->fresh()->emailed_at);
    }

    /** R15 â€” the draft detail footer exposes no send action at all. */
    public function test_draft_detail_footer_has_no_send_actions(): void
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();

        $html = $this->get(route('accounting.quotations.show', $quotation))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('accounting.quotations.send', $quotation), $html);
        $this->assertStringNotContainsString(route('accounting.quotations.email', $quotation), $html);
        $this->assertStringNotContainsString('Mark as Sent', $html);
        $this->assertStringNotContainsString('Email to Customer', $html);

        // Edit stays â€” it is the sanctioned path to Finalize & issue.
        $this->assertStringContainsString(route('accounting.quotations.edit', $quotation), $html);
    }

    /** Helper â€” a finalized (sent) quotation, the only emailable state. */
    private function sentQuotation(): Quotation
    {
        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();

        return app(QuotationService::class)->send($quotation)->fresh();
    }

    /** A3.3 Â· cost centre follows the selected branch. */
    public function test_cost_centre_defaults_to_the_selected_branch_centre()
    {
        $head = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Head Office',
            'code' => 'HO',
            'is_active' => true,
        ]);
        $west = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'West Branch',
            'code' => 'WB',
            'is_active' => true,
        ]);

        $ccA = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'West Branch Ops',
            'code' => 'WB',
            'is_active' => true,
        ]);
        $ccB = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Head Office Ops',
            'code' => 'HO',
            'is_active' => true,
        ]);

        // the first branch is the server-side default, so the first branch's centre is prefilled
        $response = $this->actingAs($this->user)->get(route('accounting.quotations.create'));
        $response->assertOk();
        $response->assertSee('name="cost_center_id"', false);
        $response->assertSee('value: '.$ccB->id, false);
        $response->assertSee('label: '.Js::from('Head Office Ops'), false);

        // the client re-sync map carries one entry per branch
        $map = json_decode($this->extractJsonObject($response->getContent(), 'QUOT_COST_CENTRE_BY_BRANCH'), true);
        $this->assertIsArray($map);
        $this->assertSame(['id' => $ccB->id, 'name' => 'Head Office Ops'], $map[(string) $head->id] ?? null);
        $this->assertSame(['id' => $ccA->id, 'name' => 'West Branch Ops'], $map[(string) $west->id] ?? null);
        $this->assertSame(['id' => $ccB->id, 'name' => 'Head Office Ops'], $map['__default__'] ?? null);
    }

    /**
     * A,D,D,A,A,Dc,A3.3 the re-sync CONTRACT, deterministic without a browser:
     * the stored value starts as a default (not an override) so a branch change
     * re-syncs, a manual pick latches `touched`, and a touched field is left
     * alone. Covers both selection channels -- the inline dropdown
     * (`item-selected`) and the global-search magnifier
     * (`global-search-selected`).
     */
    public function test_branch_change_resyncs_cost_centre_until_the_user_overrides_it(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Head Office',
            'code' => 'HO',
            'is_active' => true,
        ]);
        $other = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'West Branch',
            'code' => 'WB',
            'is_active' => true,
        ]);
        $centre = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'Head Office Ops',
            'code' => 'HO',
            'is_active' => true,
        ]);
        $otherCentre = CostCenter::create([
            'company_id' => $this->company->id,
            'name' => 'West Branch Ops',
            'code' => 'WB',
            'is_active' => true,
        ]);

        $html = $this->get(route('accounting.quotations.create'))->assertOk()->getContent();

        // 1. both branches map to their own centre, __default__ covers no selection
        $map = json_decode($this->extractJsonObject($html, 'QUOT_COST_CENTRE_BY_BRANCH'), true);
        $this->assertSame(['id' => $centre->id, 'name' => 'Head Office Ops'], $map[(string) $branch->id] ?? null);
        $this->assertSame(['id' => $otherCentre->id, 'name' => 'West Branch Ops'], $map[(string) $other->id] ?? null);
        $this->assertSame(['id' => $centre->id, 'name' => 'Head Office Ops'], $map['__default__'] ?? null);

        // 2. the stored default is NOT treated as an override, on create AND edit
        $this->assertStringContainsString('let quotCostCentreTouched = false;', $html);
        $this->assertStringContainsString(
            "if (quotCostCentreTouched || !quotCostCentreInput || typeof scopedSearchFieldSet !== 'function') return;",
            $html
        );

        // 3. the branch select drives the re-sync, and a missing branch falls back
        $this->assertStringContainsString("const key = (quotBranchSel && quotBranchSel.value) || '__default__';", $html);
        $this->assertStringContainsString(
            "const target = QUOT_COST_CENTRE_BY_BRANCH[key] || QUOT_COST_CENTRE_BY_BRANCH['__default__'];",
            $html
        );
        $this->assertStringContainsString('function quotSyncCostCentreToBranch()', $html);

        // 4. manual override latches via BOTH channels (typing, inline pick,
        //    global-search magnifier) -- all scoped to the cost-centre field only
        $this->assertStringContainsString("quotCostCentreInput.addEventListener('input', () => { quotCostCentreTouched = true; });", $html);
        $this->assertStringContainsString("['item-selected', 'global-search-selected'].forEach((evt) => {", $html);
        $this->assertStringContainsString('if (quotCostCentreField && field === quotCostCentreField) {', $html);

        // 5. the edit form ships the same contract (stored value still a default)
        //    NB: edit only opens for drafts -- a finalized quote redirects to show.
        $draft = app(QuotationService::class)->create($this->payload(), $this->user->id)->fresh();
        $editHtml = $this->get(route('accounting.quotations.edit', $draft))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('let quotCostCentreTouched = false;', $editHtml);
        $this->assertStringContainsString('function quotSyncCostCentreToBranch()', $editHtml);
    }

    /**
     * A failed dispatch is the one case where re-sending IS the View action
     * (the recipient was never notified), and Print/PDF must leave the app in a
     * new tab -- the R14 in-app A4 sheet stays one click away behind Preview.
     */
    public function test_view_modal_footer_offers_resend_email_after_failed_dispatch(): void
    {
        $quotation = $this->sentQuotation();
        (new \App\Mail\QuotationMail($quotation))->failed(new \RuntimeException('Mailbox unavailable'));

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        // §3.2 — a FAILED dispatch is the one case where re-sending is the action,
        // and it goes immediately (no confirmation). A first send says "Send to
        // customer" and asks first instead.
        $this->assertStringContainsString("d.can.email ? (d.email_failed ? B('email', \"Re-send email\"", $html);
        $this->assertStringContainsString("B('email', \"Send to customer\"", $html);
        $this->assertStringContainsString("if (d.email_failed) { window.QDispatch.open(d); return; }", $html);
        $this->assertStringContainsString('window.QDispatch.ask(d, vid);', $html);

        // Print/PDF opens a new tab; Preview keeps the in-app sheet reachable.
        $this->assertStringContainsString("window.open(d.urls.print, '_blank', 'noopener')", $html);
        $this->assertStringContainsString("if (act === 'preview') { openQuotePrint(quotViewing); return; }", $html);
        $this->assertStringContainsString('B(\'preview\', "Preview"', $html);
    }

    /**
     * Maker-checker is enforced where it matters -- accept/decline carry
     * sod:quotation -- so the creator can never approve their own quote.
     * Fulfilment of an ALREADY accepted quote is not an approval step, so the
     * conversion action (and the POST route) must stay available to them.
     */
    public function test_accepted_quotation_offers_convert_to_invoice_to_the_creator(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->update(['status' => Quotation::STATUS_ACCEPTED]);

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('"convert":true', $html);
    }

    /** The creator can now fulfil an accepted quotation they authored. */
    public function test_creator_may_convert_their_own_accepted_quotation(): void
    {
        $quotation = $this->sentQuotation();
        $quotation->update(['status' => Quotation::STATUS_ACCEPTED]);

        $this->actingAs($this->user)
            ->post(route('accounting.quotations.convert-to-invoice', $quotation))
            ->assertRedirect(route('accounting.invoices.show', \App\Models\Invoice::first()));
    }

    /** The four helper sentences were dropped from the register + View footer. */
    public function test_register_and_view_drop_removed_helper_copy(): void
    {
        $sent = $this->sentQuotation();
        $sent->update(['status' => Quotation::STATUS_ACCEPTED]);

        $html = $this->get(route('accounting.quotations.index'))->assertOk()->getContent();

        foreach ([
            'Track, send and convert customer quotations',
            'Awaiting customer response',
            'Saves to the draft. Finalize issues it as sent.',
            'convert to invoice to fulfil.',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }

    /**
     * Appendix A styling contract: deep-teal modal header with a WHITE glyph,
     * the Draft -> Sent -> Accepted -> Converted timeline rail, a compact active
     * status tile in #DCE6E7 with no green top bar, and a properly sized
     * empty-state icon (the halo previously had no rule at all, so the bare SVG
     * filled the table cell).
     */
    public function test_modal_and_tile_styling_matches_the_spec(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('#11454b 0%, #0c3539 55%, #0a2e32 100%', $css);
        $this->assertMatchesRegularExpression('/\.q2-mic \{[^}]*color: #fff;/', $css);
        $this->assertMatchesRegularExpression('/\.q2-step::before \{[^}]*height: 2px;/', $css);
        $this->assertMatchesRegularExpression('/\.q2-fbox\.is-active \{[^}]*background: #DCE6E7;/', $css);
        $this->assertStringNotContainsString('.q2-fbox.is-active::before', $css);
        $this->assertMatchesRegularExpression('/\.q2-empty-halo \{[^}]*width: 34px;/', $css);
        $this->assertMatchesRegularExpression('/\.q2-empty-halo svg \{[^}]*height: 15px;/', $css);
    }

    /**
     * Confirm dialogs must never render an empty body. The app-wide default is
     * a one-sentence header (title only) -- that case drops the body and gives
     * the header the vertical room; a confirm that carries body copy keeps an
     * airy body so the sentence is never edge-to-edge.
     */
    public function test_confirm_dialogs_never_render_an_empty_body(): void
    {
        $js = (string) file_get_contents(resource_path('js/feedback.js'));
        $css = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('solo: !hasBody', $js);
        $this->assertStringContainsString("? '<div class=\"dlg-body' + (airy ? ' dlg-body--airy' : '') + '\">'", $js);
        $this->assertStringContainsString(": '')", $js);
        $this->assertStringContainsString('dlg-head--solo', $css);
        $this->assertMatchesRegularExpression('/\.dlg-head--solo \{[^}]*padding: 22px 20px 24px;/', $css);
        $this->assertMatchesRegularExpression('/\.dlg-body--airy \{[^}]*min-height: 104px;/', $css);
    }

    /**
     * R5 A6 status tiles + rail Views must actually filter. The status branch used
     * `when($cond, fn($q, $s) => ...->where('status', $s))`, but Builder::when()
     * hands the callback the CONDITION (true) -- not the value -- so every
     * single-status filter compiled to `where status = 1` and matched nothing.
     */
    public function test_register_status_filters_actually_filter(): void
    {
        $service = app(QuotationService::class);

        $draft = $service->create($this->payload(['reference' => 'FILTER-DRAFT']), $this->user->id)->fresh();
        $sent = $service->send(
            $service->create($this->payload(['reference' => 'FILTER-SENT']), $this->user->id)->fresh()
        )->fresh();
        // The rail deep-links R6 payloads for rows off the current page, so the
        // table itself is asserted via its per-row "View" buttons.
        $tableRows = function (string $html): int {
            return substr_count($html, 'class="q2-ibtn" data-qview=');
        };

        // ?status=sent -> exactly the sent row, never the empty state
        $sentHtml = $this->get(route('accounting.quotations.index', ['status' => 'sent']))
            ->assertOk()
            ->assertSee($sent->quotation_number)
            ->assertDontSee(__('No quotations found. Adjust your filters or create a new quotation.'))
            ->getContent();
        $this->assertSame(1, $tableRows($sentHtml), 'status=sent must list only sent rows');

        // ?status=draft -> exactly the draft row
        $draftHtml = $this->get(route('accounting.quotations.index', ['status' => 'draft']))
            ->assertOk()
            ->getContent();
        $this->assertSame(1, $tableRows($draftHtml), 'status=draft must list only draft rows');

        // ?status=open spans draft + sent, unfiltered lists both
        $this->assertSame(2, $tableRows($this->get(route('accounting.quotations.index', ['status' => 'open']))->getContent()));
        $this->assertSame(2, $tableRows($this->get(route('accounting.quotations.index'))->getContent()));

        // the CSV export shares the same filter
        $export = $this->get(route('accounting.quotations.export', ['status' => 'sent']));
        $export->assertOk();
        $this->assertStringContainsString($sent->quotation_number, $export->streamedContent());
    }

    public function test_sales_order_status_filter_actually_filters(): void
    {
        // Same latent defect: a boolean-condition `when()` passed `true` into the
        // where(). Guarded here because the quotations fix must not be reverted
        // back to the two-argument form.
        $controller = new \App\Http\Controllers\Accounting\SalesOrderController();
        $source = file_get_contents(app_path('Http/Controllers/Accounting/SalesOrderController.php'));
        $quotations = file_get_contents(app_path('Http/Controllers/Accounting/QuotationController.php'));

        foreach (['SalesOrderController' => $source, 'QuotationController' => $quotations] as $label => $file) {
            $this->assertStringNotContainsString(
                "fn(\$q, \$s) => \$q->where('status', \$s)",
                $file,
                "{$label} status filter must capture the value, not the when() condition"
            );
        }
    }

    private function extractJsonObject(string $html, string $marker): string
    {
        $pos = strpos($html, $marker);
        $this->assertNotFalse($pos, 'Marker not found: ' . $marker);
        $start = strpos($html, '{', $pos);
        $depth = 0;
        for ($i = $start; $i < strlen($html); $i++) {
            if ($html[$i] === '{') {
                $depth++;
            } elseif ($html[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($html, $start, $i - $start + 1);
                }
            }
        }
        $this->fail('Unbalanced JSON after ' . $marker);
    }
}
