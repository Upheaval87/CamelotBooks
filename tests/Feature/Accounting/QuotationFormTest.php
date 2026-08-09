<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\Accounting\QuotationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
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
            'prefix' => 'QTN-',
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
            ->assertSee('quot-dropzone')
            ->assertSee('valid_until')
            ->assertSee('Internal Notes')
            ->assertSee('quotation-form', false);
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

    public function test_store_submit_for_approval_marks_sent(): void
    {
        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'action' => 'submit_for_approval',
        ]));

        $response->assertRedirect();

        $quotation = Quotation::where('company_id', $this->company->id)->firstOrFail();
        $this->assertSame('sent', $quotation->status);
    }

    public function test_store_save_and_new_redirects_to_create(): void
    {
        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'action' => 'save_and_new',
        ]));

        $response->assertRedirect(route('accounting.quotations.create'));
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

    public function test_store_uploads_attachments(): void
    {
        Storage::fake('public');

        $response = $this->post(route('accounting.quotations.store'), $this->payload([
            'files' => [
                File::create('quote.pdf', 100, 'application/pdf'),
                File::create('spec.png', 100, 'image/png'),
            ],
        ]));

        $response->assertRedirect();

        $quotation = Quotation::where('company_id', $this->company->id)->firstOrFail();
        $this->assertCount(2, $quotation->attachments);

        foreach ($quotation->attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment->file_path);
        }

        $this->assertSame('quote.pdf', $quotation->attachments[0]->name);
        $this->assertSame($this->user->id, $quotation->attachments[0]->uploaded_by);
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

    public function test_update_deletes_flagged_attachment(): void
    {
        Storage::fake('public');

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);

        $attachment = $quotation->attachments()->create([
            'company_id' => $this->company->id,
            'name' => 'old-spec.pdf',
            'file_path' => 'quotation-attachments/keep.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $this->user->id,
        ]);

        Storage::disk('public')->put($attachment->file_path, 'pdf-bytes');

        $response = $this->put(route('accounting.quotations.update', $quotation), $this->payload([
            'delete_documents' => [$attachment->id],
        ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_store_rejects_oversized_files(): void
    {
        Storage::fake('public');

        $response = $this->from(route('accounting.quotations.create'))
            ->post(route('accounting.quotations.store'), $this->payload([
                'files' => [File::create('huge.pdf', 11000, 'application/pdf')],
            ]));

        $response->assertSessionHasErrors('files.0');
        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_show_renders_attachments_card(): void
    {
        Storage::fake('public');

        $quotation = app(QuotationService::class)->create($this->payload(), $this->user->id);
        $attachment = $quotation->attachments()->create([
            'company_id' => $this->company->id,
            'name' => 'quote.pdf',
            'file_path' => 'quotation-attachments/quote.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $this->user->id,
        ]);

        $this->get(route('accounting.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Attachments')
            ->assertSee('quote.pdf');
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
}
