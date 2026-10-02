<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $bank;
    protected Account $income;
    protected Account $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'DASH',
            'name' => 'Dashboard Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        $this->income = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
        $this->expense = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'Operating Expense',
            'type' => 'expense',
            'sub_type' => 'operating',
            'is_active' => true,
        ]);
    }

    private function createBankAccount(): Account
    {
        return Account::create([
            'company_id' => $this->company->id,
            'code' => 'BK01',
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank_account' => true,
        ]);
    }

    private function postJournalPair(): void
    {
        $this->bank = $this->createBankAccount();

        $entry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-' . Carbon::now()->format('Ymd') . '-01',
            'date' => Carbon::now()->toDateString(),
            'reference' => 'PAIR-1',
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->user->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $this->bank->id,
            'debit' => 2000.00,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $this->income->id,
            'debit' => 0,
            'credit' => 2000.00,
        ]);

        $expEntry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-' . Carbon::now()->format('Ymd') . '-02',
            'date' => Carbon::now()->toDateString(),
            'reference' => 'PAIR-2',
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->user->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $expEntry->id,
            'account_id' => $this->expense->id,
            'debit' => 500.00,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $expEntry->id,
            'account_id' => $this->bank->id,
            'debit' => 0,
            'credit' => 500.00,
        ]);
    }

    public function test_dashboard_renders_for_company_admin(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Total Revenue', false);
        $response->assertSee('Total Expenses', false);
        $response->assertSee('Net Profit', false);
        $response->assertSee('Outstanding Inv.', false);
        $response->assertSee('Bills Payable', false);
        $response->assertSee('Cash &amp; Bank', false);
        $response->assertSee('This Month', false);
        $response->assertSee('class="dash8"', false);
        $response->assertSee('Revenue vs Expenses', false);
        $response->assertSee('Cash Position', false);
        $response->assertSee('Receivables Aging', false);
        $response->assertSee('Upcoming Bills &amp; Taxes', false);
        $response->assertSee('Quick Actions', false);
        $response->assertSee('My Tasks', false);
        $response->assertSee('Recent Activity', false);
        $response->assertSee('New Invoice', false);
        $response->assertSee(route('dashboard.export'));
    }

    public function test_period_presets_mark_active_chip(): void
    {
        $this->actingAs($this->user)->get(route('dashboard', ['period' => 'quarter']))
            ->assertSee('class="seg-t on"', false);

        $content = $this->actingAs($this->user)->get(route('dashboard', ['period' => 'ytd']))->getContent();
        $this->assertStringContainsString('class="seg-t on" href=', $content);
        $this->assertStringContainsString('aria-current="page">Year to Date</a>', $content);
        $this->assertStringNotContainsString('aria-current="page">This Month</a>', $content);
    }

    public function test_kpi_math_matches_gl(): void
    {
        $this->postJournalPair();

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('2,000.00', false);   // revenue
        $response->assertSee('500.00', false);     // expenses
        $response->assertSee('1,500.00', false);   // net
        $response->assertSee('Revenue vs Expenses', false);
        $response->assertSee('class="cols"', false);
        $response->assertSee('bar rev', false);
        $response->assertSee('Receivables Aging', false);
        $response->assertSee('class="track"', false);
    }

    public function test_recent_activity_lists_posted_journal(): void
    {
        $this->postJournalPair();

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertSee('Journal posted', false);
        $response->assertSee('PAIR-1', false);
        $response->assertSee('Today', false);

        // The full-page route lives in the JSON modal payload, where Blade's @json
        // (json_encode with flags 15) escapes every slash as \/ .
        $href = str_replace('/', '\/', route('accounting.journal-entries.show', JournalEntry::first()->id));
        $this->assertStringContainsString($href, $response->getContent());
    }

    public function test_activity_rows_expose_a_detail_modal(): void
    {
        $this->postJournalPair();

        $content = $this->actingAs($this->user)->get(route('dashboard'))->assertStatus(200)->getContent();

        $this->assertStringContainsString('class="act-row" data-act="0"', $content);
        $this->assertStringContainsString('window.DASH_ACTIVITY', $content);
        $this->assertStringContainsString('id="actScrim"', $content);
        $this->assertStringContainsString('aria-modal="true"', $content);
    }

    public function test_upcoming_bills_expose_a_detail_modal(): void
    {
        $vendor = \App\Models\Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Office Depot',
            'currency' => 'USD',
        ]);

        Bill::create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'BILL-500',
            'bill_date' => Carbon::now()->subDays(10)->toDateString(),
            'due_date' => Carbon::now()->addDays(5)->toDateString(),
            'status' => Bill::STATUS_APPROVED,
            'amount' => 420.00,
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        $content = $this->actingAs($this->user)->get(route('dashboard'))->assertStatus(200)->getContent();

        $this->assertStringContainsString('Office Depot', $content);
        $this->assertStringContainsString('data-bill="0"', $content);
        $this->assertStringContainsString('window.DASH_UPCOMING', $content);
        $this->assertStringContainsString('id="billScrim"', $content);
    }

    public function test_cash_position_card_lists_accounts(): void
    {
        $this->postJournalPair();

        $content = $this->actingAs($this->user)->get(route('dashboard'))->assertStatus(200)->getContent();

        $this->assertStringContainsString('Cash on hand', $content);
        $this->assertStringContainsString('Main Bank', $content);
        $this->assertStringContainsString('2,000.00', $content);
        $this->assertStringContainsString('class="mix"', $content);
    }

    public function test_zero_states_render_without_data(): void
    {
        $content = $this->actingAs($this->user)->get(route('dashboard'))->assertStatus(200)->getContent();

        // no cash/bank accounts, no bills, no activity in a bare company
        $this->assertStringContainsString('No cash or bank accounts yet', $content);
        $this->assertStringContainsString('Nothing due in the next 45 days', $content);
        $this->assertStringContainsString('No activity recorded yet', $content);
    }

    public function test_export_csv_streams_summary(): void
    {
        $this->postJournalPair();

        $response = $this->actingAs($this->user)->get(route('dashboard.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Total Revenue', $csv);
        $this->assertStringContainsString('Net Profit', $csv);
        $this->assertStringContainsString('2000.00', $csv);
        $this->assertStringContainsString('500.00', $csv);
    }

    public function test_permission_gating_hides_quick_action_tiles(): void
    {
        $this->user->removeRole('company_admin');
        $this->user->assignRole('viewer');

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('New Invoice', false);
        $response->assertDontSee('New Bill', false);
        $response->assertDontSee('Record Payment', false);
        $response->assertDontSee('New Journal', false);
        $response->assertDontSee('Reconcile', false);
        $response->assertDontSee(route('accounting.invoices.create'), false);
        $response->assertDontSee(route('accounting.journal-entries.create'), false);
        // read-only tiles stay visible
        $response->assertSee('Receivables Aging', false);
    }

    public function test_outstanding_invoices_and_aging_query_stored_columns(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Acme Buyer',
            'payment_terms' => 'net_30',
            'currency' => 'USD',
        ]);
        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-1001',
            'invoice_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->toDateString(),
            'status' => Invoice::STATUS_SENT,
            'amount' => 300.00,
            'currency' => 'USD',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'))->assertStatus(200);

        $response->assertSee('Outstanding Inv.', false);
        $response->assertSee('300.00', false);
        $response->assertSee('Receivables Aging', false);
    }
}