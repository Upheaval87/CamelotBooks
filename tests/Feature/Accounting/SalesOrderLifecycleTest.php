<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NumberingSequence;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Accounting\SalesOrderService;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SALES ORDERS MODULE §5.2 / §8.2 backend contracts.
 *
 * These lock the server-side half of the module: the status machine, the
 * numbering sequences, the walk-in capture, the stock ledger and the invoice
 * cross-link. The view-layer parity work is verified separately.
 */
class SalesOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'SOCO',
            'name' => 'Sales Order Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
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

        // Sales orders + delivery notes both need a per-year sequence row.
        foreach ([['sales_order', 'SO-'], ['delivery_note', 'DN-']] as [$type, $prefix]) {
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
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'customer_id' => Customer::create([
                'company_id' => $this->company->id,
                'name' => 'Acme Ltd',
                'payment_terms' => 'Net 30',
                'payment_terms_days' => 30,
                'is_active' => true,
            ])->id,
            'order_date' => '2026-08-01',
            'expected_delivery_date' => '2026-08-20',
            'delivery_address' => '1 Depot Road, Blantyre',
            'contact_person' => 'Jane Doe',
            'email' => 'jane@acme.test',
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

    // ── numbering + creation ───────────────────────────────────────────────

    public function test_draft_is_numbered_so_year_seq4_and_totals_are_computed(): void
    {
        $order = app(SalesOrderService::class)->create($this->payload(), $this->user->id);

        $this->assertSame('SO-' . now()->format('Y') . '-0001', $order->sales_order_number);
        $this->assertSame(SalesOrder::STATUS_DRAFT, $order->status);
        $this->assertEqualsWithDelta(400.0, (float) $order->amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $order->tax_total, 0.001);
        $this->assertEqualsWithDelta(400.0, (float) $order->total, 0.001);
        $this->assertCount(1, $order->lines);
        // Customer name is snapshotted for the print sheet.
        $this->assertSame('Acme Ltd', $order->customer_name);
    }

    public function test_numbering_advances_per_order(): void
    {
        $service = app(SalesOrderService::class);
        $service->create($this->payload(), $this->user->id);
        $second = $service->create($this->payload(), $this->user->id);

        $this->assertSame('SO-' . now()->format('Y') . '-0002', $second->sales_order_number);
    }

    // ── walk-in capture (R10) ──────────────────────────────────────────────

    public function test_walk_in_release_materialises_a_customer_row_and_links_it(): void
    {
        $payload = $this->payload(['customer_id' => null, 'customer_name' => 'Walk-In Buyer']);
        unset($payload['customer_id']);

        $order = app(SalesOrderService::class)->create(
            $payload,
            $this->user->id,
            SalesOrderService::MODE_RELEASE
        );

        $this->assertSame(SalesOrder::STATUS_SENT, $order->status);
        $this->assertNotNull($order->customer_id);
        $this->assertDatabaseHas('customers', [
            'id' => $order->customer_id,
            'company_id' => $this->company->id,
            'name' => 'Walk-In Buyer',
        ]);
        $this->assertSame('Walk-In Buyer', $order->customer_name);
    }

    // ── release gating (R9) ────────────────────────────────────────────────

    public function test_release_is_blocked_until_delivery_and_contact_are_present(): void
    {
        $service = app(SalesOrderService::class);

        $payload = $this->payload(['delivery_address' => null, 'contact_person' => null]);

        $this->expectException(\InvalidArgumentException::class);
        $service->assertNoReleaseGaps($payload);
    }

    public function test_blank_description_still_counts_as_a_complete_line(): void
    {
        $payload = $this->payload();
        $payload['lines'][0]['description'] = null;

        $gaps = app(SalesOrderService::class)->releaseGaps($payload);

        $this->assertNotContains('line', $gaps, 'A blank description must not create a release gap (R9).');
    }

    // ── lifecycle ──────────────────────────────────────────────────────────

    public function test_confirm_records_a_reservation_without_consuming_stock(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);

        $confirmed = $service->confirm($order->fresh(), $this->user->id);

        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'type' => 'reservation',
            'quantity' => 4,
        ]);
    }

    public function test_partial_fulfilment_records_a_delivery_note_and_keeps_the_order_open(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);
        $service->confirm($order->fresh(), $this->user->id);

        $result = $service->recordFulfilment($order->fresh(), [
            'date' => '2026-08-10',
            'received_by' => 'Warehouse Sam',
            'lines' => [['line_id' => $order->lines->first()->id, 'qty' => 1]],
        ], $this->user->id);

        $this->assertFalse($result['complete']);
        $this->assertSame('DN-' . now()->format('Y') . '-0001', $result['delivery_note_no']);
        $this->assertSame(SalesOrder::STATUS_CONFIRMED, $order->fresh()->status);
        $this->assertDatabaseHas('order_fulfilments', [
            'sales_order_id' => $order->id,
            'quantity' => 1,
            'received_by' => 'Warehouse Sam',
        ]);
    }

    public function test_fulfilment_quantity_cannot_exceed_the_remaining(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);
        $service->confirm($order->fresh(), $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordFulfilment($order->fresh(), [
            'date' => '2026-08-10',
            'lines' => [['line_id' => $order->lines->first()->id, 'qty' => 99]],
        ], $this->user->id);
    }

    public function test_complete_fulfilment_flips_to_fulfilled_and_stores_the_delivery_note(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);
        $service->confirm($order->fresh(), $this->user->id);

        $result = $service->recordFulfilment($order->fresh(), [
            'date' => '2026-08-10',
            'received_by' => 'Warehouse Sam',
            'lines' => [['line_id' => $order->lines->first()->id, 'qty' => 4]],
        ], $this->user->id);

        $fresh = $order->fresh();

        $this->assertTrue($result['complete']);
        $this->assertSame(SalesOrder::STATUS_FULFILLED, $fresh->status);
        $this->assertSame('DN-' . now()->format('Y') . '-0001', $fresh->delivery_note_no);
        $this->assertNotNull($fresh->delivered_on);
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'relief',
            'quantity' => 4,
        ]);
    }

    public function test_fulfilment_is_refused_before_confirmation(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordFulfilment($order->fresh(), [
            'date' => '2026-08-10',
            'lines' => [['line_id' => $order->lines->first()->id, 'qty' => 1]],
        ], $this->user->id);
    }

    // ── email (§5.4 format validation) ─────────────────────────────────────

    public function test_malformed_email_is_rejected(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordEmailed($order, 'not-an-email', $this->user->id);
    }

    public function test_valid_email_is_stamped_and_audited(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);

        $order = $service->recordEmailed($order, 'jane@acme.test', $this->user->id);

        $this->assertSame('jane@acme.test', $order->emailed_to);
        $this->assertNotNull($order->emailed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'emailed']);
    }

    // ── cancel / void (§5.5 retention) ─────────────────────────────────────

    public function test_cancel_retains_the_document_and_its_lines(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);

        $cancelled = $service->cancel($order, 'Customer withdrew', $this->user->id);

        $this->assertSame(SalesOrder::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame('Customer withdrew', $cancelled->cancel_reason);
        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'status' => SalesOrder::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseCount('sales_order_lines', 1);
    }

    public function test_draft_delete_is_allowed_but_released_orders_are_retained(): void
    {
        $service = app(SalesOrderService::class);
        $draft = $service->create($this->payload(), $this->user->id);
        $service->destroy($draft, $this->user->id);

        $this->assertDatabaseMissing('sales_orders', ['id' => $draft->id]);

        $released = $service->create($this->payload(), $this->user->id);
        $service->release($released, $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $service->destroy($released->fresh(), $this->user->id);
    }

    public function test_duplicate_creates_a_new_draft_with_a_fresh_number(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);

        $copy = $service->duplicate($order, $this->user->id);

        $this->assertSame(SalesOrder::STATUS_DRAFT, $copy->status);
        $this->assertNotSame($order->sales_order_number, $copy->sales_order_number);
        $this->assertCount(1, $copy->lines);
    }

    // ── invoice conversion (§5.2) ──────────────────────────────────────────

    public function test_invoice_requires_a_fulfilled_order(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $service->createInvoice($order, $this->user->id);
    }

    public function test_fulfilled_order_converts_to_an_invoice_with_a_cross_link(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);
        $service->confirm($order->fresh(), $this->user->id);
        $service->recordFulfilment($order->fresh(), [
            'date' => '2026-08-10',
            'lines' => [['line_id' => $order->lines->first()->id, 'qty' => 4]],
        ], $this->user->id);

        $invoice = $service->createInvoice($order->fresh(), $this->user->id);

        $this->assertEqualsWithDelta(400.0, (float) $invoice->amount, 0.001);
        $this->assertSame($invoice->id, $order->fresh()->converted_invoice_id);
        // Second attempt returns the SAME invoice rather than double-billing.
        $this->assertSame($invoice->id, $service->createInvoice($order->fresh(), $this->user->id)->id);
    }

    // ── audit trail (§5.5) ─────────────────────────────────────────────────

    public function test_every_transition_is_audited(): void
    {
        $service = app(SalesOrderService::class);
        $order = $service->create($this->payload(), $this->user->id);
        $service->release($order, $this->user->id);
        $service->confirm($order->fresh(), $this->user->id);

        $actions = DB::table('audit_logs')
            ->where('auditable_type', SalesOrder::class)
            ->where('auditable_id', $order->id)
            ->pluck('action')
            ->all();

        $this->assertContains('created', $actions);
        $this->assertContains('released', $actions);
        $this->assertContains('confirmed', $actions);
    }
}