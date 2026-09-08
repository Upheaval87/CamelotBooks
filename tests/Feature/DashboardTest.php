<?php

namespace Tests\Feature;

use App\Models\Account;
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
        $response->assertSee('Outstanding Invoices', false);
        $response->assertSee('Bills Payable', false);
        $response->assertSee('Cash &amp; Bank', false);
        $response->assertSee('This Month', false);
        $response->assertSee('Welcome to your dashboard', false);
        $response->assertSee('New Invoice', false);
        $response->assertSee(route('dashboard.export'));
    }

    public function test_period_presets_mark_active_chip(): void
    {
        $this->actingAs($this->user)->get(route('dashboard', ['period' => 'quarter']))
            ->assertSee('dash-chip on', false);

        $content = $this->actingAs($this->user)->get(route('dashboard', ['period' => 'ytd']))->getContent();
        $this->assertStringContainsString('dash-chip on" aria-current="page">Year to Date</a>', $content);
        $this->assertStringNotContainsString('dash-chip on" aria-current="page">This Month</a>', $content);
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
        $response->assertSee('dash-chart-bars', false);
        $response->assertSee('dash-bar-rev', false);
        $response->assertSee('Receivables Aging', false);
        $response->assertSee('dash-aging-track', false);
    }

    public function test_recent_activity_lists_posted_journal(): void
    {
        $this->postJournalPair();

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertSee('Journal posted — PAIR-1', false);
        $response->assertSee('Today', false);
        $response->assertSee(route('accounting.journal-entries.show', JournalEntry::first()->id));
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
        $response->assertDontSee('>New Invoice</span>', false);
        $response->assertDontSee('>New Bill</span>', false);
        $response->assertDontSee('>Record Payment</span>', false);
        $response->assertDontSee('>New Journal</span>', false);
        $response->assertDontSee('>Reconcile</span>', false);
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

        $response->assertSee('Outstanding Invoices', false);
        $response->assertSee('300.00', false);
        $response->assertSee('Receivables Aging', false);
    }
}