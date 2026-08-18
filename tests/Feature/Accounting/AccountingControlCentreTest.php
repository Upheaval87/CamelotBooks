<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingControlCentreTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $assetAccount;
    protected Account $expenseAccount;
    protected CostCenter $costCenter;
    protected JournalEntry $draftEntry;
    protected JournalEntry $postedEntry;
    protected FiscalYear $fy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'ACCTCO',
            'name' => 'Accounting Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        foreach (['banking', 'fixed_assets', 'inventory', 'payroll', 'pos', 'purchasing', 'budgets'] as $feature) {
            FeatureManagement::enable($this->company->id, $feature);
        }

        $this->assetAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '5000',
            'name' => 'Expenses',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->costCenter = CostCenter::create([
            'company_id' => $this->company->id,
            'code' => 'CC-01',
            'name' => 'Operations',
            'is_active' => true,
        ]);

        $this->fy = FiscalYear::create([
            'company_id' => $this->company->id,
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        $this->draftEntry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-0001',
            'date' => '2026-08-15',
            'memo' => 'Test draft entry',
            'is_adjusting_entry' => false,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $this->draftEntry->id,
            'account_id' => $this->assetAccount->id,
            'debit' => 500.00,
            'credit' => 0,
            'company_id' => $this->company->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $this->draftEntry->id,
            'account_id' => $this->expenseAccount->id,
            'debit' => 0,
            'credit' => 500.00,
            'company_id' => $this->company->id,
        ]);

        $this->postedEntry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-0002',
            'date' => '2026-08-10',
            'memo' => 'Test posted entry',
            'is_adjusting_entry' => false,
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $this->postedEntry->id,
            'account_id' => $this->assetAccount->id,
            'debit' => 1000.00,
            'credit' => 0,
            'company_id' => $this->company->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $this->postedEntry->id,
            'account_id' => $this->expenseAccount->id,
            'debit' => 0,
            'credit' => 1000.00,
            'company_id' => $this->company->id,
        ]);

        ExchangeRate::create([
            'company_id' => $this->company->id,
            'currency_from' => 'EUR',
            'currency_to' => 'USD',
            'rate' => 1.12,
            'effective_date' => '2026-08-01',
        ]);
    }

    public function test_journal_entries_index_renders(): void
    {
        $response = $this->get(route('accounting.journal-entries.index'));
        $response->assertStatus(200);
        $response->assertSee('Journal Entries');
        $response->assertSee('JE-0001');
        $response->assertSee('JE-0002');
    }

    public function test_journal_entries_create_renders(): void
    {
        $response = $this->get(route('accounting.journal-entries.create'));
        $response->assertStatus(200);
        $response->assertSee('New Journal Entry');
        $response->assertSee('je-form');
    }

    public function test_journal_entries_show_renders(): void
    {
        $response = $this->get(route('accounting.journal-entries.show', $this->draftEntry));
        $response->assertStatus(200);
        $response->assertSee('JE-0001');
        $response->assertSee('Draft');
    }

    public function test_journal_entries_edit_renders_draft(): void
    {
        $response = $this->get(route('accounting.journal-entries.edit', $this->draftEntry));
        $response->assertStatus(200);
        $response->assertSee('je-form');
    }

    public function test_journal_entries_show_posted(): void
    {
        $response = $this->get(route('accounting.journal-entries.show', $this->postedEntry));
        $response->assertStatus(200);
        $response->assertSee('JE-0002');
        $response->assertSee('Posted');
    }

    public function test_general_ledger_index_renders(): void
    {
        $response = $this->get(route('accounting.general-ledger.index'));
        $response->assertStatus(200);
        $response->assertSee('General Ledger');
    }

    public function test_general_ledger_account_renders(): void
    {
        $response = $this->get(route('accounting.general-ledger.account', $this->assetAccount));
        $response->assertStatus(200);
        $response->assertSee('1000');
        $response->assertSee('Cash');
    }

    public function test_trial_balance_renders(): void
    {
        $response = $this->get(route('accounting.trial-balance.index'));
        $response->assertStatus(200);
        $response->assertSee('Trial Balance');
        $response->assertSee('Balanced');
    }

    public function test_fiscal_years_index_renders(): void
    {
        $response = $this->get(route('accounting.fiscal-years.index'));
        $response->assertStatus(200);
        $response->assertSee('Fiscal Years');
        $response->assertSee('FY 2026');
    }

    public function test_fiscal_years_create_renders(): void
    {
        $response = $this->get(route('accounting.fiscal-years.create'));
        $response->assertStatus(200);
        $response->assertSee('New Fiscal Year');
    }

    public function test_fiscal_years_show_renders(): void
    {
        $response = $this->get(route('accounting.fiscal-years.show', $this->fy));
        $response->assertStatus(200);
        $response->assertSee('FY 2026');
    }

    public function test_periods_index_renders(): void
    {
        $response = $this->get(route('accounting.periods.index'));
        $response->assertStatus(200);
        $response->assertSee('Accounting Periods');
    }

    public function test_cost_centers_index_renders(): void
    {
        $response = $this->get(route('accounting.cost-centers.index'));
        $response->assertStatus(200);
        $response->assertSee('Cost Centres');
        $response->assertSee('CC-01');
    }

    public function test_cost_centers_create_renders(): void
    {
        $response = $this->get(route('accounting.cost-centers.create'));
        $response->assertStatus(200);
        $response->assertSee('New Cost Centre');
    }

    public function test_cost_centers_show_renders(): void
    {
        $response = $this->get(route('accounting.cost-centers.show', $this->costCenter));
        $response->assertStatus(200);
        $response->assertSee('CC-01');
        $response->assertSee('Operations');
    }

    public function test_exchange_rates_index_renders(): void
    {
        $response = $this->get(route('accounting.exchange-rates.index'));
        $response->assertStatus(200);
        $response->assertSee('Exchange Rates');
        $response->assertSee('EUR');
    }

    public function test_exchange_rates_create_renders(): void
    {
        $response = $this->get(route('accounting.exchange-rates.create'));
        $response->assertStatus(200);
        $response->assertSee('New Exchange Rate');
    }

    public function test_account_classification_index_renders(): void
    {
        $response = $this->get(route('accounting.account-classification.index'));
        $response->assertStatus(200);
        $response->assertSee('Account Classification');
        $response->assertSee('1000');
    }

    public function test_account_classification_create_renders(): void
    {
        $response = $this->get(route('accounting.account-classification.create'));
        $response->assertStatus(200);
        $response->assertSee('Classification');
    }

    public function test_all_ac_css_classes_present(): void
    {
        $cssFiles = glob(public_path('build/assets/app-*.css'));
        $cssPath = $cssFiles[0] ?? null;
        if (!$cssPath || !file_exists($cssPath)) {
            $this->markTestSkipped('Build assets not found');
        }
        $css = file_get_contents($cssPath);
        $classes = ['ac-wrap', 'ac-page-head', 'ac-card', 'ac-table', 'ac-badge', 'ac-btn', 'ac-ci', 'ac-g4', 'ac-f'];
        foreach ($classes as $cls) {
            $this->assertStringContainsString('.' . $cls, $css, "CSS class .$cls not found in bundle");
        }
    }

    public function test_chart_of_accounts_index_renders(): void
    {
        $response = $this->get(route('accounting.accounts.index'));
        $response->assertStatus(200);
        $response->assertSee('Chart of Accounts');
        $response->assertSee('coa-kpis');
        $response->assertSee('coa-toolbar');
        $response->assertSee('coa-table');
        $response->assertSee('Total Accounts');
        $response->assertSee('1000');
        $response->assertSee('Cash');
    }

    public function test_chart_of_accounts_index_filter_by_type(): void
    {
        $response = $this->get(route('accounting.accounts.index', ['type' => 'asset']));
        $response->assertStatus(200);
        $response->assertSee('coa-table');
        $response->assertSee('Asset');
    }

    public function test_chart_of_accounts_index_filter_by_status(): void
    {
        $response = $this->get(route('accounting.accounts.index', ['status' => 'active']));
        $response->assertStatus(200);
        $response->assertSee('coa-table');
    }

    public function test_coa_css_classes_present(): void
    {
        $cssFiles = glob(public_path('build/assets/app-*.css'));
        $cssPath = $cssFiles[0] ?? null;
        if (!$cssPath || !file_exists($cssPath)) {
            $this->markTestSkipped('Build assets not found');
        }
        $css = file_get_contents($cssPath);
        $classes = ['coa-wrap', 'coa-head', 'coa-kpis', 'coa-kpi', 'coa-toolbar', 'coa-card', 'coa-table', 'coa-badge', 'coa-tchip', 'coa-btn-cta', 'coa-btn-ghost', 'coa-grp', 'coa-sub'];
        foreach ($classes as $cls) {
            $this->assertStringContainsString('.' . $cls, $css, "CSS class .$cls not found in bundle");
        }
    }
}
