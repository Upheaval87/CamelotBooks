<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ReportAuditLog;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\Accounting\JournalPostingEngine;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function setPermissionsTeamId;

class EquityStatementTest extends TestCase
{
    use RefreshDatabase;

    protected JournalPostingEngine $engine;

    protected User $user;

    protected Company $company;

    protected Account $cash;

    protected Account $retainedEarnings;

    protected Account $revenue;

    protected Account $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(JournalPostingEngine::class);
        $this->user = User::factory()->create();

        $this->company = Company::create([
            'name' => 'Report Test Co',
            'company_code' => 'RPT',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->cash = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $this->retainedEarnings = Account::create([
            'company_id' => $this->company->id,
            'code' => '3100',
            'name' => 'Retained Earnings',
            'type' => 'equity',
            'sub_type' => 'equity',
            'is_active' => true,
        ]);

        $this->revenue = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'income',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->expense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        $this->session(['current_company_id' => $this->company->id]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026 Q1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    private function postEquityContribution(float $amount = 5000, string $date = '2026-01-15'): void
    {
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => $date,
            'lines' => [
                ['account_id' => $this->cash->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->retainedEarnings->id, 'debit' => 0, 'credit' => $amount],
            ],
        ]);
    }

    private function getIndex(array $params = []): \Illuminate\Testing\TestResponse
    {
        $base = ['date_from' => '2026-01-01', 'date_to' => '2026-03-31', 'zero' => '1'];

        return $this->get(route('accounting.equity-statement.index', array_merge($base, $params)));
    }

    public function test_index_renders_screen_chrome_without_brand_identity(): void
    {
        $this->postEquityContribution();

        $response = $this->getIndex();

        $response->assertOk();
        $response->assertSee('Statement of Changes in Equity');
        $response->assertSee('01 Jan 2026', false);
        $response->assertSee('USD ($)', false);

        // presets + actions
        foreach (['This Month', 'This Quarter', 'YTD', 'Custom'] as $label) {
            $response->assertSee($label);
        }
        $response->assertSee('Generate');
        $response->assertSee('Clear');
        $response->assertSee('Print');
        $response->assertSee('Excel');
        $response->assertSee('PDF');

        // sheet body
        $response->assertSee('Opening (', false);
        $response->assertSee('Total Equity');
        $response->assertSee('Net Income for the Period');
        $response->assertSee('soc-table');
        $response->assertSee('Retained Earnings');
    }

    public function test_visibility_matrix_css_is_present(): void
    {
        $this->postEquityContribution();

        $html = $this->getIndex()->getContent();

        $this->assertStringContainsString('.soc-doc-h,.soc-meta,.soc-co-foot{display:none}', $html);
        $this->assertStringContainsString('@media print', $html);
        $this->assertStringContainsString('.soc-phead,.soc-filters,.soc-tools,.fr-filters,.fr-actions,.fr-btn{display:none !important}', $html);
        $this->assertStringContainsString('.soc-doc-h,.soc-meta,.soc-co-foot{display:block}', $html);
        $this->assertStringContainsString('.soc-co-foot{display:flex', $html);
        $this->assertStringContainsString('.soc-hide-zero tr.soc-zero{display:none}', $html);
        $this->assertStringContainsString('.soc-hide-zero tr.soc-zero{display:table-row}', $html);
    }

    public function test_presets_default_to_ytd_and_empty_dates_fall_back(): void
    {
        $this->postEquityContribution();

        // No params -> YTD chip active. Zero query dates act as cleared inputs.
        $html = $this->get(route('accounting.equity-statement.index', ['date_from' => '', 'date_to' => '']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="soc-chip on"', $html);
        $this->assertStringContainsString('aria-current="true"', $html);
    }

    public function test_tie_chip_visible_when_net_income_is_zero(): void
    {
        $this->postEquityContribution();

        $this->getIndex()->assertOk()->assertSee('Ties to the General Ledger');
    }

    public function test_tie_chip_hidden_when_net_income_is_nonzero(): void
    {
        $this->postEquityContribution();
        $this->engine->post([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'date' => '2026-02-10',
            'lines' => [
                ['account_id' => $this->cash->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->revenue->id, 'debit' => 0, 'credit' => 5000],
            ],
        ]);

        $this->getIndex()->assertOk()->assertDontSee('Ties to the General Ledger');
    }

    public function test_csv_uses_settings_currency_and_code_column(): void
    {
        $this->postEquityContribution();

        $response = $this->get(route('accounting.equity-statement.export-csv', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-03-31',
            'zero' => '1',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        // Headers use settings currency, never hard-coded.
        $this->assertStringContainsString('Code,Account,"Opening ($)","Movement ($)","Closing ($)"', $csv);
        $this->assertStringContainsString('Currency,"USD ($)"', $csv);
        // Code column precedes the account name; amounts are plain (no thousands separators).
        $this->assertStringContainsString('3100,"Retained Earnings",0.00,5000.00,5000.00', $csv);
        $this->assertStringContainsString('Net Income for the Period', $csv);
        $this->assertStringContainsString('Total Equity', $csv);

        $this->assertTrue(
            ReportAuditLog::query()
                ->where('report_key', 'fin.equity')
                ->where('action', ReportAuditLog::ACTION_EXCEL)
                ->exists()
        );
    }

    public function test_csv_zero_filter_drops_zero_balance_accounts(): void
    {
        $this->postEquityContribution();
        $this->createExtraEquityAccount('3200', 'Additional Paid-in Capital');

        $hidden = $this->get(route('accounting.equity-statement.export-csv', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-03-31',
            'zero' => '0',
        ]))->streamedContent();
        $this->assertStringContainsString('3100,"Retained Earnings"', $hidden);
        $this->assertStringNotContainsString('Additional Paid-in Capital', $hidden);

        $shown = $this->get(route('accounting.equity-statement.export-csv', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-03-31',
            'zero' => '1',
        ]))->streamedContent();
        $this->assertStringContainsString('3200,"Additional Paid-in Capital",0.00,0.00,0.00', $shown);
    }

    public function test_export_pdf_renders_branded_shell_with_fs_chrome(): void
    {
        $this->postEquityContribution();

        $response = $this->get(route('accounting.equity-statement.export-pdf', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-03-31',
            'zero' => '1',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
        $html = $response->getContent();

        $this->assertStringContainsString('Statement of Changes in Equity', $html);
        $this->assertStringContainsString('<div class="fs-sheet">', $html);
        $this->assertStringContainsString('fs-head', $html);
        $this->assertStringContainsString('fs-meta', $html);
        $this->assertStringContainsString('fs-foot', $html);
        $this->assertStringContainsString('Report Test Co', $html);
        $this->assertStringContainsString('<table class="fs-table">', $html);
        $this->assertStringContainsString('Total Equity', $html);
        $this->assertStringContainsString('Page 1 of 1', $html);

        $this->assertTrue(
            ReportAuditLog::query()
                ->where('report_key', 'fin.equity')
                ->where('action', ReportAuditLog::ACTION_PDF)
                ->exists()
        );
    }

    public function test_index_logs_report_audit_entry(): void
    {
        $this->postEquityContribution();
        $this->getIndex()->assertOk();

        $this->assertTrue(
            ReportAuditLog::query()
                ->where('report_key', 'fin.equity')
                ->where('action', ReportAuditLog::ACTION_VIEW)
                ->exists()
        );
    }

    public function test_branch_scope_limits_picker_and_forces_cleared_branch(): void
    {
        $branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch A',
            'code' => 'BR-A',
            'is_active' => true,
        ]);
        $branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch B',
            'code' => 'BR-B',
            'is_active' => true,
        ]);

        UserCompanyAssignment::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'role' => 'company_admin',
            'branch_ids' => [$branchA->id],
            'is_active' => true,
        ]);

        // Force an out-of-scope branch -> server clears it (no 500, picker limited).
        $html = $this->getIndex(['branch_id' => $branchB->id])->assertOk()->getContent();

        $this->assertStringContainsString('Branch A', $html);
        $this->assertStringNotContainsString('Branch B', $html);
        $this->assertStringContainsString('name="branch_id"', $html);
    }

    private function createExtraEquityAccount(string $code, string $name): Account
    {
        return Account::create([
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => $name,
            'type' => 'equity',
            'sub_type' => 'equity',
            'is_active' => true,
        ]);
    }
}