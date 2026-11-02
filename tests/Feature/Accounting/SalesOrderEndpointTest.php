<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SALES ORDERS MODULE §5.2 · the HTTP endpoint contract.
 *
 * SalesOrderLifecycleTest proves the service; this proves the wiring —
 * routes, middleware, permissions, validation and redirects.
 */
class SalesOrderEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected User $secondUser;
    protected Account $incomeAccount;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->secondUser = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'SOEP',
            'name' => 'SO Endpoint Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        foreach ([$this->user, $this->secondUser] as $u) {
            $u->companies()->attach($this->company->id, ['role' => 'company_admin']);
        }
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->secondUser->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'inventory');

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);

        foreach ([['sales_order', 'SO-'], ['delivery_note', 'DN-'], ['invoice', 'INV-']] as [$type, $prefix]) {
            NumberingSequence::create([
                'company_id' => $this->company->id,
                'document_type' => $type,
                'prefix' => $prefix,
                'padding_width' => 4,
                'next_number' => 1,
                'reset_policy' => 'annually',
                'is_active' => true,
            ]);
        }

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget 3000',
            'sku' => 'WG-3000',
            'type' => 'product',
            'tracked_as_inventory' => false,
            'sales_price' => 100,
            'unit_of_measure' => 'each',
            'income_account_id' => $this->incomeAccount->id,
            'expense_account_id' => $this->incomeAccount->id,
            'tax_rate' => 0,
            'is_taxable' => false,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Ltd',
            'payment_terms' => 'Net 30',
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'contact_person' => 'Jane Doe',
            'order_date' => '2026-08-01',
            'expected_delivery_date' => '2026-08-20',
            'delivery_address' => '1 Depot Road',
            'currency' => 'USD',
            'lines' => [[
                'product_id' => $this->product->id,
                'item_code' => 'WG-3000',
                'item_name' => 'Widget 3000',
                'description' => 'Blue widget',
                'quantity' => 4,
                'unit_price' => 100,
                'income_account_id' => $this->incomeAccount->id,
            ]],
        ], $overrides);
    }

    private function draftOrder(): SalesOrder
    {
        return SalesOrder::query()->latest('id')->firstOrFail();
    }

    /** Store a draft through HTTP so the whole stack is exercised. */
    private function postDraft(array $overrides = []): SalesOrder
    {
        $this->post(route('accounting.sales-orders.store'), $this->payload($overrides))
            ->assertRedirect();

        return $this->draftOrder();
    }

    /** Draft → sent → confirmed, with the confirm done by a second user (SOD). */
    private function confirmedOrder(): SalesOrder
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order))->assertRedirect();
        $this->actingAs($this->secondUser);
        $this->post(route('accounting.sales-orders.confirm', $order->fresh()))->assertRedirect();

        return $order->fresh();
    }

    /** A fully fulfilled order, ready for invoice conversion. */
    private function fulfilledOrder(): SalesOrder
    {
        $order = $this->confirmedOrder();

        $this->post(route('accounting.sales-orders.fulfilments', $order), [
            'date' => '2026-08-10',
            'received_by' => 'Sam',
            'lines' => [['line_id' => $order->lines()->firstOrFail()->id, 'qty' => 4]],
        ])->assertRedirect();

        return $order->fresh();
    }

    // ── store ──────────────────────────────────────────────────────────────

    public function test_store_saves_a_draft_and_redirects_to_the_order(): void
    {
        $response = $this->post(route('accounting.sales-orders.store'), $this->payload());

        $order = SalesOrder::query()->firstOrFail();

        $this->assertSame(SalesOrder::STATUS_DRAFT, $order->status);
        $response->assertRedirect(route('accounting.sales-orders.show', $order));
        $response->assertSessionHas('success');
    }

    public function test_store_with_mode_release_sends_immediately(): void
    {
        $this->post(route('accounting.sales-orders.store'), $this->payload(['mode' => 'release']));

        $this->assertSame(SalesOrder::STATUS_SENT, SalesOrder::query()->firstOrFail()->status);
    }

    public function test_store_in_release_mode_refuses_incomplete_input(): void
    {
        $this->post(route('accounting.sales-orders.store'), $this->payload([
            'mode' => 'release',
            'contact_person' => null,
        ]))->assertSessionHasErrors();

        $this->assertSame(0, SalesOrder::query()->count());
    }

    public function test_store_rejects_input_with_neither_registry_match_nor_walk_in_name(): void
    {
        $this->post(route('accounting.sales-orders.store'), $this->payload([
            'customer_id' => null,
            'customer_name' => null,
        ]))->assertSessionHasErrors('customer_id');

        $this->assertSame(0, SalesOrder::query()->count());
    }

    public function test_save_and_new_returns_an_empty_create_form(): void
    {
        $this->post(route('accounting.sales-orders.store'), $this->payload([
            'action' => 'save_and_new',
        ]))->assertRedirect(route('accounting.sales-orders.create'));

        $this->assertSame(1, SalesOrder::query()->count());
    }

    public function test_store_walk_in_creates_and_links_a_customer(): void
    {
        $payload = $this->payload(['customer_type' => 'walkin', 'customer_name' => 'Walk-In Buyer']);
        unset($payload['customer_id']);

        $this->post(route('accounting.sales-orders.store'), $payload)->assertRedirect();

        $order = SalesOrder::query()->firstOrFail();
        $this->assertNotNull($order->customer_id);
        $this->assertDatabaseHas('customers', ['id' => $order->customer_id, 'name' => 'Walk-In Buyer']);
    }

    public function test_store_rejects_a_line_without_an_income_account(): void
    {
        $payload = $this->payload();
        unset($payload['lines'][0]['income_account_id']);

        $this->post(route('accounting.sales-orders.store'), $payload)
            ->assertSessionHasErrors('lines.0.income_account_id');
    }

    public function test_store_rejects_a_delivery_date_before_the_order_date(): void
    {
        $this->post(route('accounting.sales-orders.store'), $this->payload([
            'expected_delivery_date' => '2026-07-01',
        ]))->assertSessionHasErrors('expected_delivery_date');
    }

    // ── update ─────────────────────────────────────────────────────────────

    public function test_update_conflicts_once_the_order_is_released(): void
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order))->assertRedirect();

        $this->put(route('accounting.sales-orders.update', $order->fresh()), $this->payload([
            'memo' => 'late edit',
        ]))->assertStatus(409);
    }

    public function test_update_persists_a_draft_change(): void
    {
        $order = $this->postDraft();

        $this->put(route('accounting.sales-orders.update', $order), $this->payload([
            'memo' => 'Called ahead',
        ]))->assertRedirect();

        $this->assertSame('Called ahead', $order->fresh()->memo);
    }

    // ── lifecycle verbs ────────────────────────────────────────────────────

    public function test_release_then_confirm_moves_the_order_forward(): void
    {
        $order = $this->postDraft();

        $this->post(route('accounting.sales-orders.release', $order))->assertRedirect();
        $this->assertSame(SalesOrder::STATUS_SENT, $order->fresh()->status);

        $this->actingAs($this->secondUser);
        $this->post(route('accounting.sales-orders.confirm', $order->fresh()))->assertRedirect();
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_the_author_can_release_their_own_draft(): void
    {
        $order = $this->postDraft();

        $this->post(route('accounting.sales-orders.release', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(SalesOrder::STATUS_SENT, $order->fresh()->status);
    }

    public function test_confirm_is_blocked_by_segregation_of_duties_for_the_author(): void
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order));

        // The author may not confirm their own order (maker-checker).
        $this->post(route('accounting.sales-orders.confirm', $order->fresh()))->assertStatus(403);
    }

    public function test_cancel_retains_the_document_and_its_lines(): void
    {
        $order = $this->postDraft();

        $this->post(route('accounting.sales-orders.cancel', $order), [
            'reason' => 'Customer withdrew',
        ])->assertRedirect();

        $this->assertSame(SalesOrder::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertDatabaseHas('sales_orders', ['id' => $order->id]);
        $this->assertDatabaseCount('sales_order_lines', 1);
    }

    public function test_void_requires_a_reason(): void
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order));

        $this->post(route('accounting.sales-orders.void', $order->fresh()))
            ->assertSessionHasErrors('reason');

        $this->post(route('accounting.sales-orders.void', $order->fresh()), [
            'reason' => 'Duplicate entry',
        ])->assertRedirect();

        $this->assertSame(SalesOrder::STATUS_VOID, $order->fresh()->status);
    }

    public function test_duplicate_of_a_released_order_creates_a_new_draft(): void
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order));

        $this->post(route('accounting.sales-orders.duplicate', $order->fresh()))->assertRedirect();

        $this->assertSame(2, SalesOrder::query()->count());
        $this->assertSame(1, SalesOrder::query()->where('status', SalesOrder::STATUS_DRAFT)->count());
    }

    public function test_destroy_removes_a_draft_but_guards_a_released_order(): void
    {
        $draft = $this->postDraft();
        $this->delete(route('accounting.sales-orders.destroy', $draft))->assertRedirect();
        $this->assertDatabaseMissing('sales_orders', ['id' => $draft->id]);

        $released = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $released));

        $this->delete(route('accounting.sales-orders.destroy', $released->fresh()))->assertStatus(409);
        $this->assertDatabaseHas('sales_orders', ['id' => $released->id]);
    }

    // ── fulfil ─────────────────────────────────────────────────────────────

    public function test_partial_fulfilment_keeps_the_order_confirmed(): void
    {
        $order = $this->confirmedOrder();

        $this->post(route('accounting.sales-orders.fulfilments', $order), [
            'date' => '2026-08-10',
            'received_by' => 'Sam',
            'lines' => [['line_id' => $order->lines()->firstOrFail()->id, 'qty' => 1]],
        ])->assertRedirect();

        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
        $this->assertDatabaseHas('order_fulfilments', ['sales_order_id' => $order->id]);
    }

    public function test_complete_fulfilment_flips_to_fulfilled(): void
    {
        $order = $this->confirmedOrder();

        $this->post(route('accounting.sales-orders.fulfilments', $order), [
            'date' => '2026-08-10',
            'received_by' => 'Sam',
            'lines' => [['line_id' => $order->lines()->firstOrFail()->id, 'qty' => 4]],
        ])->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame(SalesOrder::STATUS_FULFILLED, $fresh->status);
        $this->assertSame('DN-' . now()->format('Y') . '-0001', $fresh->delivery_note_no);
    }

    public function test_fulfil_rejects_a_quantity_beyond_the_remaining(): void
    {
        $order = $this->confirmedOrder();

        $this->post(route('accounting.sales-orders.fulfilments', $order), [
            'date' => '2026-08-10',
            'lines' => [['line_id' => $order->lines()->firstOrFail()->id, 'qty' => 99]],
        ])->assertSessionHasErrors();
    }

    // ── email ──────────────────────────────────────────────────────────────

    public function test_send_email_rejects_a_malformed_address(): void
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order));

        $this->post(route('accounting.sales-orders.send-email', $order->fresh()), [
            'email' => 'nope',
        ])->assertSessionHasErrors('email');
    }

    public function test_send_email_stamps_the_order(): void
    {
        $order = $this->postDraft();
        $this->post(route('accounting.sales-orders.release', $order));

        $this->post(route('accounting.sales-orders.send-email', $order->fresh()), [
            'email' => 'jane@acme.test',
        ])->assertRedirect();

        $this->assertSame('jane@acme.test', $order->fresh()->emailed_to);
    }

    // ── invoice ────────────────────────────────────────────────────────────

    public function test_convert_to_invoice_requires_fulfilment(): void
    {
        $order = $this->confirmedOrder();

        // Act as the second user: the creator is blocked by SOD, which is a
        // separate guard from the unfulfilled-status refusal asserted here.
        $this->post(route('accounting.sales-orders.convert-to-invoice', $order))
            ->assertSessionHasErrors(['status']);
    }

    public function test_convert_to_invoice_links_the_generated_invoice(): void
    {
        $order = $this->fulfilledOrder();

        $this->post(route('accounting.sales-orders.convert-to-invoice', $order))
            ->assertRedirect();

        $this->assertNotNull($order->fresh()->converted_invoice_id);
    }

    public function test_convert_to_invoice_is_reachable_through_the_new_route_alias(): void
    {
        $order = $this->fulfilledOrder();

        $this->post(route('accounting.sales-orders.invoice', $order))->assertRedirect();

        $this->assertNotNull($order->fresh()->converted_invoice_id);
    }

    // ── register + exports ─────────────────────────────────────────────────

    public function test_register_renders_the_order_and_filters_it_out_by_status(): void
    {
        $order = $this->postDraft();

        $this->get(route('accounting.sales-orders.index'))
            ->assertOk()
            ->assertSee($order->sales_order_number, false);

        $this->get(route('accounting.sales-orders.index', ['status' => 'cancelled']))
            ->assertOk()
            ->assertDontSee($order->sales_order_number, false);
    }

    public function test_export_streams_a_csv(): void
    {
        $order = $this->postDraft();

        $response = $this->get(route('accounting.sales-orders.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString($order->sales_order_number, $response->streamedContent());
    }

    public function test_print_renders_the_order_sheet(): void
    {
        $order = $this->postDraft();

        $this->get(route('accounting.sales-orders.print', $order))
            ->assertOk()
            ->assertSee('Sales Order', false);
    }

    public function test_print_renders_the_delivery_sheet(): void
    {
        $order = $this->fulfilledOrder();

        $this->get(route('accounting.sales-orders.print', ['order' => $order, 'doc' => 'delivery']))
            ->assertOk();
    }

    public function test_pdf_generates_a_document(): void
    {
        $order = $this->postDraft();

        $response = $this->get(route('accounting.sales-orders.pdf', $order));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    // ── authorisation ──────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('accounting.sales-orders.index'))->assertRedirect(route('login'));
    }
}