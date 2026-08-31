<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashPositionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'banking');

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    protected function bankAccount(array $overrides = []): Account
    {
        return Account::create(array_merge([
            'company_id' => $this->company->id,
            'code' => 'BK01',
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank' => true,
            'is_bank_account' => true,
            'opening_balance' => 1000,
        ], $overrides));
    }

    protected function pettyCashAccount(): Account
    {
        return Account::create([
            'company_id' => $this->company->id,
            'code' => 'PC01',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_petty_cash' => true,
        ]);
    }

    protected function postedEntry(int $bankAccountId, float $debit, string $date, string $number, string $memo = 'Test entry', string $sourceModule = 'sales_receipt'): JournalEntry
    {
        $entry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => $number,
            'date' => $date,
            'reference' => 'REF-' . $number,
            'memo' => $memo,
            'status' => JournalEntry::STATUS_POSTED,
            'source_module' => $sourceModule,
            'created_by' => $this->user->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $bankAccountId,
            'debit' => $debit,
            'credit' => 0,
            'memo' => 'Cash side',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $this->incomeAccount->id,
            'debit' => 0,
            'credit' => $debit,
            'memo' => 'Offset',
        ]);

        return $entry;
    }

    public function test_index_renders_full_page(): void
    {
        $bank = $this->bankAccount();
        $this->pettyCashAccount();
        $je = $this->postedEntry($bank->id, 250, now()->toDateString(), 'JE-CP-0001', 'Cash position inflow');

        BankTransaction::create([
            'company_id' => $this->company->id,
            'bank_account_id' => $bank->id,
            'journal_entry_id' => $je->id,
            'type' => 'deposit',
            'source_type' => 'journal',
            'source_id' => $je->id,
            'date' => now()->toDateString(),
            'description' => 'Opening deposit',
            'reference' => 'DEP-0001',
            'amount' => 250.00,
            'is_reconciled' => false,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('accounting.cash-position.index'));

        $response->assertOk();
        $response->assertSee('cp2', false);
        $response->assertSee('Cash Position');
        $response->assertSee('How much cash we hold, where, and what moved.');
        $response->assertSee('This Month');
        $response->assertSee('Reconcile');
        $response->assertSee('Transfer');
        $response->assertSee('New Cash Transaction');
        $response->assertSee('More');
        $response->assertSee('Cash Receipts');
        $response->assertSee('Cash Payments');
        $response->assertSee('Bank Accounts');
        $response->assertSee('Cash Accounts');
        $response->assertSee('Bank Reconciliation');
        $response->assertSee('Cash Forecast');
        $response->assertSee('Cash Flow Statement');
        $response->assertSee('General Ledger');
        $response->assertSee('Opening');
        $response->assertSee('+ Receipts');
        $response->assertSee('minus; Payments', false);
        $response->assertSee('Closing');
        $response->assertSee('this period');
        $response->assertSee('Bank Balance');
        $response->assertSee('Cash on Hand');
        $response->assertSee('Unreconciled');
        $response->assertSee('View unreconciled');
        $response->assertSee('class="net', false); // net pill moved into the chips row
        $response->assertSee('Cash Position by Account', false);
        $response->assertSee('Transfers In', false);
        $response->assertSee('Transfers Out', false);
        $response->assertSee('View ledger');
        $response->assertSee('Main Bank');
        $response->assertSee('Petty Cash');
        $response->assertSee('Cash Movement', false);
        $response->assertSee('Inflows', false);
        $response->assertSee('Outflows', false);
        $response->assertSee('Recent Cash Transactions', false);
        $response->assertSee('Opening deposit');
        $response->assertSee('DEP-0001');
        $response->assertSee(route('accounting.bank-reconciliation.index', $bank->id));
        $response->assertDontSee('No cash or bank accounts match the current filters.');
    }

    public function test_index_movement_math_combines_opening_and_period_lines(): void
    {
        $bank = $this->bankAccount();

        $this->postedEntry($bank->id, 100, now()->subMonth()->toDateString(), 'JE-CP-0002', 'Last month inflow');
        $this->postedEntry($bank->id, 250, now()->toDateString(), 'JE-CP-0003', 'This month inflow');

        $response = $this->actingAs($this->user)->get(route('accounting.cash-position.index'));

        $response->assertOk();
        $response->assertSee('1,100.00'); // opening = opening_balance 1000 + pre-period 100
        $response->assertSee('250.00');   // receipts for the period
        $response->assertSee('1,350.00'); // closing = opening + receipts - payments
        $response->assertSee('This Month');
    }

    public function test_filters_persist_in_form_and_export_links(): void
    {
        $this->bankAccount();

        $response = $this->actingAs($this->user)->get(
            route('accounting.cash-position.index', ['period' => 'quarter', 'q' => 'Main'])
        );

        $response->assertOk();
        $response->assertSee('value="quarter"', false);
        $response->assertSee('value="Main"', false);
        $response->assertSee('q=Main');
        $response->assertSee('period=quarter');
    }

    public function test_index_filters_restrict_accounts(): void
    {
        $this->bankAccount(['code' => 'BK01', 'name' => 'Main Bank']);
        $this->bankAccount(['code' => 'BK02', 'name' => 'Savings Account']);

        $response = $this->actingAs($this->user)->get(
            route('accounting.cash-position.index', ['q' => 'Savings'])
        );

        $response->assertOk();
        $response->assertSee('Savings Account');
        $response->assertSee('1 accounts', false); // only the Savings row survives the q filter
    }

    public function test_recent_transactions_renders_debit_credit_and_link(): void
    {
        $bank = $this->bankAccount();
        $je = $this->postedEntry($bank->id, 250, now()->toDateString(), 'JE-CP-0004');

        BankTransaction::create([
            'company_id' => $this->company->id,
            'bank_account_id' => $bank->id,
            'journal_entry_id' => $je->id,
            'type' => 'withdrawal',
            'source_type' => 'journal',
            'source_id' => $je->id,
            'date' => now()->toDateString(),
            'description' => 'Office rent',
            'reference' => 'WD-0001',
            'amount' => -150.00,
            'is_reconciled' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('accounting.cash-position.index'));

        $response->assertOk();
        $response->assertSee('Office rent');
        $response->assertSee('WD-0001');
        $response->assertSee('BK01');
        $response->assertSee('150.00');
        $response->assertSee(route('accounting.journal-entries.show', $je->id));
        $response->assertSee('View');
    }

    public function test_empty_state_when_no_cash_or_bank_accounts(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.cash-position.index'));

        $response->assertOk();
        $response->assertSee('No cash or bank accounts match the current filters.');
        $response->assertSee('No receipts in this period.');
        $response->assertSee('No payments in this period.');
        $response->assertSee('No bank transactions in this period.');
    }

    public function test_advanced_panel_lives_inside_filter_form_scope(): void
    {
        $this->bankAccount();

        $response = $this->actingAs($this->user)->get(route('accounting.cash-position.index'));

        $response->assertOk();

        $html = $response->getContent();

        // The Alpine x-data scope is hoisted onto the <form> so the sibling .advpanel
        // can see `adv` (previously it lived on .filterbar, which left .advpanel out of scope).
        $this->assertStringContainsString('id="cp2-form" x-data="{ period:', $html);

        // The .advpanel must be a child of that same form (appears before </form>).
        $formStart = strpos($html, '<form method="GET"');
        $formEnd = strpos($html, '</form>', $formStart);
        $formBlock = substr($html, $formStart, $formEnd - $formStart);
        $this->assertStringContainsString('class="advpanel"', $formBlock);
        $this->assertStringContainsString('x-show="adv"', $formBlock);

        // Filterbar itself no longer carries its own x-data.
        $this->assertStringNotContainsString('class="filterbar" x-data=', $html);
    }

    public function test_ledger_drill_opens_period_aware_account_statement(): void
    {
        $bank = $this->bankAccount(['opening_balance' => 1000]);

        // Pre-period entry (last month) + in-period entry (this month).
        $this->postedEntry($bank->id, 100, now()->subMonth()->toDateString(), 'JE-GL-0001', 'Pre-period inflow');
        $this->postedEntry($bank->id, 250, now()->toDateString(), 'JE-GL-0002', 'In-period inflow');

        $dateFrom = now()->startOfMonth()->toDateString();
        $dateTo = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($this->user)->get(
            route('accounting.general-ledger.account', array_merge([$bank->id], ['date_from' => $dateFrom, 'date_to' => $dateTo]))
        );

        $response->assertOk();
        // Period-aware opening: the drill pre-fills the statement's date fields with the
        // selected range so the statement's Opening/Closing match the Cash Position page.
        $response->assertSee('value="' . $dateFrom . '"', false);
        $response->assertSee('1,100.00'); // opening = opening_balance 1000 + pre-period 100
        $response->assertSee('250.00');   // period debit
        $response->assertSee('1,350.00'); // closing = 1100 + 250

        // Cash Position drill links carry the period to the same route.
        $cp = $this->actingAs($this->user)->get(route('accounting.cash-position.index'));
        $cp->assertOk();
        $cp->assertSee('general-ledger/' . $bank->id);
        $cp->assertSee('date_from=' . $dateFrom);
        $cp->assertSee('date_to=' . $dateTo);
    }

    public function test_export_csv_streams_movement(): void
    {
        $this->bankAccount();

        $response = $this->actingAs($this->user)->get(route('accounting.cash-position.export-csv'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Cash Position', $csv);
        $this->assertStringContainsString('Main Bank', $csv);
        $this->assertStringContainsString('TOTAL', $csv);
        $this->assertStringContainsString('Transfers In', $csv);
        $this->assertStringContainsString('Closing', $csv);
    }

    public function test_print_and_export_pdf_render(): void
    {
        $this->bankAccount();
        $this->pettyCashAccount();

        $print = $this->actingAs($this->user)->get(route('accounting.cash-position.print'));
        $print->assertOk();
        $print->assertSee('Cash Position');
        $print->assertSee('Summary', false);
        $print->assertSee('Period Opening', false);
        $print->assertSee('Closing Balance', false);
        $print->assertSee('Cash Position by Account', false);
        $print->assertSee('Transfers In', false);

        $pdf = $this->actingAs($this->user)->get(route('accounting.cash-position.export-pdf'));
        $pdf->assertOk();
        $pdf->assertSee('Cash Position by Account', false);
    }
}
