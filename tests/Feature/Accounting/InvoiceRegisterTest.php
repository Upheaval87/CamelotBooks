<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\FeatureManagement;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §2/§3/§5.2 — the AR register and the collections queue: shell markup, the
 * server-side period/status/sort filters behind the toolbar, both CSV exports
 * and the permission that gates them.
 */
class InvoiceRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();
        $this->company = Company::create([
            'name' => 'AR Register Co',
            'company_code' => 'ARR',
            'is_active' => true,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->user->update(['current_company_id' => $this->company->id]);
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'sales');

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Nkhwazi Traders',
            'email' => 'ap@nkhwazi.test',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ]);
    }

    private function invoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-100',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'amount' => 100,
            'grand_total' => 100,
            'amount_paid' => 0,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    public function test_index_renders_the_register_shell(): void
    {
        $this->invoice();

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.index'));
        $r->assertOk();

        // APPENDIX A register shell (.invm suite — NOT the legacy .suite/.arm markup).
        $r->assertSee('class="invm"', false);
        $r->assertSee('id="listView"', false);
        $r->assertSee('Outstanding AR');
        $r->assertSee('id="kAR"', false);
        $r->assertSee('Collections queue');
        $r->assertSee('id="inv-list-form"', false);
        $r->assertSee('id="periodBar"', false);
        $r->assertSee('class="stiles"', false);
        $r->assertSee('class="irow"', false);
        $r->assertSee('id="iBody"', false);
        $r->assertSee('id="showing"', false);
        $r->assertSee('INV-100');
        $r->assertSee('Nkhwazi Traders');
        // Workspace + payment modals + confirm/success boxes + script globals.
        $r->assertSee('id="vwRef"', false);
        $r->assertSee('id="pySave"', false);
        $r->assertSee('id="cbTitle"', false);
        $r->assertSee('id="okBody"', false);
        $r->assertSee('window.INV_DATA', false);
        // Register nav: collections queue page + CSV export links.
        $r->assertSee('invoices/collections', false);
        $r->assertSee('invoices/export', false);
        // Guards against template holes (undefined variables leaking as literal text).
        $r->assertDontSee('undefined');
    }

    public function test_register_renders_the_print_preview_and_payload_company_block(): void
    {
        // R16 (§5.3): the register ships the in-app print fallback overlay, and
        // the payload carries the per-company brand/remittance block + cost
        // centre the sheet renders from (spec js.txt L9-L35 hardcodes samples).
        $this->invoice();

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.index'));
        $r->assertOk();
        $r->assertSee('id="printView"', false);
        $r->assertSee('id="oSheet"', false);
        $r->assertSee('id="pvRef"', false);
        $r->assertSee('id="pvPrint"', false);
        $r->assertSee('id="pvBack"', false);
        $r->assertSee('Print preview');
        // The payload is plain json_encode (literal quotes) with non-ASCII
        // escaped, e.g. a null terms renders as \u2014.
        $r->assertSee('"mark":"A"', false);
        $r->assertSee('"bank_account":"0000000000"', false);
        $r->assertSee('"cc":"\u2014"', false);
    }

    public function test_post_outcome_is_delivered_once_then_consumed(): void
    {
        // §5.1 — after posting, the register shows the success outcome; a
        // refresh/Back must NOT replay it (the controller pulls, not gets).
        $outcome = ['mode' => 'post', 'ref' => 'INV-2026-0042', 'cust' => 'Walk-in Customer'];

        $first = $this->actingAs($this->user)
            ->withSession(['invOutcome' => $outcome])
            ->get(route('accounting.invoices.index'));
        $first->assertOk();
        // @json() hex-escapes the quotes, so match the raw script line + the ref.
        $first->assertSee('window.INV_OUTCOME = {', false);
        $first->assertSee('INV-2026-0042');

        $second = $this->actingAs($this->user)->get(route('accounting.invoices.index'));
        $second->assertOk();
        $second->assertSee('window.INV_OUTCOME = null', false);
        $second->assertDontSee('INV-2026-0042');
    }

    public function test_index_period_preset_filters_the_table_server_side(): void
    {
        $inMonth = $this->invoice(['invoice_number' => 'INV-IN-MONTH', 'invoice_date' => now(), 'due_date' => now()->addDays(30)]);
        // Draft keeps it out of the collections-queue rail, which is deliberately
        // period-independent - only the table should be filtered by `period`.
        $lastMonth = $this->invoice([
            'invoice_number' => 'INV-LAST-MONTH',
            'invoice_date' => now()->subMonthNoOverflow()->setDay(3),
            'due_date' => now()->subMonthNoOverflow()->setDay(3)->addDays(30),
            'status' => 'draft',
        ]);

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.index', ['period' => 'm0']));
        $r->assertOk();
        $r->assertSee('INV-IN-MONTH');
        $r->assertDontSee('INV-LAST-MONTH');

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.index', ['period' => 'm-1']));
        $r->assertOk();
        $r->assertSee('INV-LAST-MONTH');
        $r->assertDontSee('INV-IN-MONTH');

        $this->assertNotSame($inMonth->id, $lastMonth->id);
    }

    public function test_index_status_and_sort_parameters_are_accepted(): void
    {
        $this->invoice(['invoice_number' => 'INV-SENT']);
        $this->invoice(['invoice_number' => 'INV-DRAFT', 'status' => 'draft']);

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.index', ['status' => 'draft']));
        $r->assertOk();
        $r->assertSee('INV-DRAFT');
        $r->assertDontSee('INV-SENT');

        // §5.2 sort values - a bad normaliser shows up as a 500 on the default load.
        foreach (['new', 'old', 'high', 'bal', 'due', 'amount_desc', 'amount_asc'] as $sort) {
            $r = $this->actingAs($this->user)->get(route('accounting.invoices.index', ['sort' => $sort]));
            $this->assertSame(200, $r->getStatusCode(), "sort={$sort} returned " . $r->getStatusCode());
        }
    }

    public function test_collections_renders_the_queue_page(): void
    {
        $this->invoice();

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.collections'));
        $r->assertOk();
        $r->assertSee('id="queueView"', false);
        $r->assertSee('Collections Queue');
        $r->assertSee('Outstanding AR');
        $r->assertSee('Overdue value');
        $r->assertSee('Due ≤ 7 days');
        $r->assertSee('kpis k3', false);
        $r->assertSee('Back to invoices');
        $r->assertSee('Export queue');
        $r->assertSee('INV-100');
        $r->assertSee('1 invoice in queue');
    }

    public function test_collections_puts_the_worst_aged_invoice_first(): void
    {
        $this->invoice([
            'invoice_number' => 'INV-DUE-5D',
            'due_date' => now()->addDays(5),
        ]);
        $this->invoice([
            'invoice_number' => 'INV-40D-LATE',
            'due_date' => now()->subDays(40),
        ]);

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.collections'));
        $r->assertOk();
        $content = $r->getContent();
        $r->assertSee('INV-40D-LATE');
        $r->assertSee('INV-DUE-5D');

        $this->assertLessThan(
            strpos($content, 'INV-DUE-5D'),
            strpos($content, 'INV-40D-LATE'),
            'The most overdue invoice must lead the queue.'
        );
        $r->assertSee('40d over');
        $r->assertSee('5 days');
    }

    public function test_collections_shows_the_empty_state_when_nothing_is_open(): void
    {
        $this->invoice(['status' => 'paid', 'amount_paid' => 100]);

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.collections'));
        $r->assertOk();
        $r->assertSee('Queue is clear');
        $r->assertSee('No open invoices with outstanding balances.');
        $r->assertSee('0 invoices in queue');
    }

    public function test_register_csv_export_streams_the_filtered_rows(): void
    {
        $this->invoice(['invoice_number' => 'INV-CSV']);

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.export'));
        $r->assertOk();
        $r->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $csv = $r->streamedContent();
        $this->assertStringContainsString('Invoice #', $csv);
        $this->assertStringContainsString('INV-CSV', $csv);
        $this->assertStringContainsString('Nkhwazi Traders', $csv);
    }

    public function test_collections_csv_export_streams_the_queue(): void
    {
        $this->invoice(['invoice_number' => 'INV-QUEUE-CSV']);

        $r = $this->actingAs($this->user)->get(route('accounting.invoices.collections.export'));
        $r->assertOk();
        $r->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $csv = $r->streamedContent();
        $this->assertStringContainsString('Invoice #', $csv);
        $this->assertStringContainsString('INV-QUEUE-CSV', $csv);
        $this->assertStringContainsString('Days overdue', $csv);
    }

    public function test_csv_exports_are_denied_without_the_export_permission(): void
    {
        $viewer = \App\Models\User::factory()->create();
        $viewer->companies()->attach($this->company->id, ['role' => 'bookkeeper']);
        setPermissionsTeamId($this->company->id);
        $viewer->assignRole('bookkeeper');

        $r = $this->actingAs($viewer)->get(route('accounting.invoices.export'));
        $r->assertForbidden();

        $r = $this->actingAs($viewer)->get(route('accounting.invoices.collections.export'));
        $r->assertForbidden();
    }
}
