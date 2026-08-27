<?php

namespace Tests\Feature\Reporting;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ReportAuditLog;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAuditScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;
    protected Account $expenseAccount;
    protected Account $assetAccount;
    protected Account $bankAccount;
    protected Account $liabilityAccount;
    protected Account $equityAccount;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'AUDTEST',
            'name' => 'Audit Test Co',
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'banking');

        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'HQ', 'code' => 'BR01']);

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '4000',
            'name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'revenue',
            'is_active' => true,
        ]);
        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '6000',
            'name' => 'Rent Expense', 'type' => 'expense', 'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);
        $this->assetAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1000',
            'name' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset',
            'is_active' => true,
        ]);
        $this->bankAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1100',
            'name' => 'Bank Account', 'type' => 'asset', 'sub_type' => 'current_asset',
            'is_active' => true, 'is_bank_account' => true,
        ]);
        $this->liabilityAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '2000',
            'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
        $this->equityAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '3000',
            'name' => 'Retained Earnings', 'type' => 'equity', 'sub_type' => 'equity',
            'is_active' => true,
        ]);

        // Posted journal entry: income 1000, expense 500, asset credit 500
        $je = JournalEntry::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'journal_number' => 'JE-AUD-001',
            'date' => now()->subDays(10), 'status' => 'posted',
            'created_by' => $this->user->id,
            'total_debit' => 1000, 'total_credit' => 1000,
        ]);
        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $this->incomeAccount->id, 'debit' => 1000, 'credit' => 0]);
        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $this->expenseAccount->id, 'debit' => 0, 'credit' => 500]);
        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $this->assetAccount->id, 'debit' => 0, 'credit' => 500]);
    }

    // ── Audit: VIEW ──

    public function test_income_statement_index_logs_view(): void
    {
        $this->actingAs($this->user)->get(route('accounting.income-statement.index', [
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'report_key' => 'fin.income',
            'action' => ReportAuditLog::ACTION_VIEW,
        ]);
    }

    public function test_balance_sheet_index_logs_view(): void
    {
        $this->actingAs($this->user)->get(route('accounting.balance-sheet.index'))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'report_key' => 'fin.position',
            'action' => ReportAuditLog::ACTION_VIEW,
        ]);
    }

    public function test_cash_flow_index_logs_view(): void
    {
        $this->actingAs($this->user)->get(route('accounting.cash-flow.index', [
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'report_key' => 'fin.cashflow',
            'action' => ReportAuditLog::ACTION_VIEW,
        ]);
    }

    public function test_ar_aging_summary_logs_view(): void
    {
        $this->actingAs($this->user)->get(route('accounting.aging.ar-summary'))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'action' => ReportAuditLog::ACTION_VIEW,
        ]);
    }

    public function test_ap_aging_summary_logs_view(): void
    {
        $this->actingAs($this->user)->get(route('accounting.aging.ap-summary'))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'action' => ReportAuditLog::ACTION_VIEW,
        ]);
    }

    // ── Audit: EXCEL ──

    public function test_income_statement_csv_logs_excel(): void
    {
        $this->actingAs($this->user)->get(route('accounting.income-statement.export-csv', [
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'report_key' => 'fin.income',
            'action' => ReportAuditLog::ACTION_EXCEL,
            'output_format' => 'csv',
        ]);
    }

    public function test_aging_csv_logs_excel(): void
    {
        $this->actingAs($this->user)->get(route('accounting.aging.export-csv', [
            'type' => 'ar',
            'as_of_date' => now()->format('Y-m-d'),
        ]))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'action' => ReportAuditLog::ACTION_EXCEL,
            'output_format' => 'csv',
        ]);
    }

    // ── Audit: PDF ──

    public function test_balance_sheet_pdf_logs_pdf(): void
    {
        $this->actingAs($this->user)->get(route('accounting.balance-sheet.export-pdf'))->assertOk();

        $this->assertDatabaseHas('report_audit_log', [
            'user_id' => $this->user->id,
            'report_key' => 'fin.position',
            'action' => ReportAuditLog::ACTION_PDF,
        ]);
    }

    // ── Audit: filters captured ──

    public function test_audit_log_captures_branch_filter(): void
    {
        $this->actingAs($this->user)->get(route('accounting.income-statement.index', [
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'branch_id' => $this->branch->id,
        ]))->assertOk();

        $log = ReportAuditLog::where('user_id', $this->user->id)
            ->where('report_key', 'fin.income')
            ->latest()->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->branch->id, $log->filters['branchId']);
    }

    // ── Audit: Model ──

    public function test_report_audit_log_model_create(): void
    {
        $log = ReportAuditLog::log(
            userId: $this->user->id,
            companyId: $this->company->id,
            reportKey: 'fin.income',
            action: ReportAuditLog::ACTION_VIEW,
            filters: ['branch_id' => null, 'date_from' => '2026-01-01'],
        );

        $this->assertDatabaseHas('report_audit_log', [
            'id' => $log->id,
            'report_key' => 'fin.income',
            'action' => 'VIEW',
        ]);
        $this->assertEquals('2026-01-01', $log->filters['date_from']);
    }

    // ── Schedule CRUD ──

    public function test_schedule_index_renders(): void
    {
        $this->actingAs($this->user)->get(route('accounting.report-schedules.index'))->assertOk();
    }

    public function test_schedule_create_renders(): void
    {
        $this->actingAs($this->user)->get(route('accounting.report-schedules.create'))->assertOk();
    }

    public function test_schedule_store_persists(): void
    {
        $this->actingAs($this->user)->post(route('accounting.report-schedules.store'), [
            'report_key' => 'fin.income',
            'frequency' => 'MONTHLY',
            'recipients' => 'cfo@test.com, accounting@test.com',
            'format' => 'PDF',
            'filters' => '{"branch_id": null, "date_from": "2026-01-01"}',
        ])->assertRedirect();

        $this->assertDatabaseHas('report_schedules', [
            'report_key' => 'fin.income',
            'frequency' => 'MONTHLY',
            'format' => 'PDF',
            'created_by' => $this->user->id,
        ]);

        $schedule = ReportSchedule::where('report_key', 'fin.income')->first();
        $this->assertEquals(['cfo@test.com', 'accounting@test.com'], $schedule->recipients);
        $this->assertTrue($schedule->active);
    }

    public function test_schedule_edit_renders(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.position',
            'filters' => [],
            'frequency' => 'WEEKLY',
            'recipients' => ['test@test.com'],
            'format' => 'EXCEL',
            'active' => true,
            'created_by' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)->get(route('accounting.report-schedules.edit', $schedule->id))->assertOk();
    }

    public function test_schedule_update_persists(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.cashflow',
            'filters' => [],
            'frequency' => 'DAILY',
            'recipients' => ['old@test.com'],
            'format' => 'PDF',
            'active' => true,
            'created_by' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)->put(route('accounting.report-schedules.update', $schedule->id), [
            'frequency' => 'WEEKLY',
            'recipients' => 'new@test.com',
            'format' => 'EXCEL',
            'filters' => '{}',
        ])->assertRedirect();

        $this->assertDatabaseHas('report_schedules', [
            'id' => $schedule->id,
            'frequency' => 'WEEKLY',
            'format' => 'EXCEL',
        ]);
    }

    public function test_schedule_toggle(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.ar-aging',
            'filters' => [],
            'frequency' => 'MONTHLY',
            'recipients' => ['x@test.com'],
            'format' => 'PDF',
            'active' => true,
            'created_by' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)->post(route('accounting.report-schedules.toggle', $schedule->id))->assertRedirect();
        $this->assertDatabaseHas('report_schedules', ['id' => $schedule->id, 'active' => false]);
    }

    public function test_schedule_destroy(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.ap-aging',
            'filters' => [],
            'frequency' => 'DAILY',
            'recipients' => ['x@test.com'],
            'format' => 'PDF',
            'active' => true,
            'created_by' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)->delete(route('accounting.report-schedules.destroy', $schedule->id))->assertRedirect();
        $this->assertDatabaseMissing('report_schedules', ['id' => $schedule->id]);
    }

    // ── Schedule isDue ──

    public function test_schedule_is_due_when_never_run(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.income', 'filters' => [], 'frequency' => 'DAILY',
            'recipients' => ['x@test.com'], 'format' => 'PDF', 'active' => true,
            'created_by' => $this->user->id, 'company_id' => $this->company->id,
        ]);
        $this->assertTrue($schedule->isDue());
    }

    public function test_schedule_not_due_when_recently_run(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.income', 'filters' => [], 'frequency' => 'DAILY',
            'recipients' => ['x@test.com'], 'format' => 'PDF', 'active' => true,
            'created_by' => $this->user->id, 'company_id' => $this->company->id,
            'last_run_at' => now()->subHours(1), 'last_run_status' => ReportSchedule::STATUS_SUCCESS,
        ]);
        $this->assertFalse($schedule->isDue());
    }

    public function test_schedule_due_after_interval(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.income', 'filters' => [], 'frequency' => 'DAILY',
            'recipients' => ['x@test.com'], 'format' => 'PDF', 'active' => true,
            'created_by' => $this->user->id, 'company_id' => $this->company->id,
            'last_run_at' => now()->subHours(25),
        ]);
        $this->assertTrue($schedule->isDue());
    }

    public function test_inactive_schedule_never_due(): void
    {
        $schedule = ReportSchedule::create([
            'report_key' => 'fin.income', 'filters' => [], 'frequency' => 'DAILY',
            'recipients' => ['x@test.com'], 'format' => 'PDF', 'active' => false,
            'created_by' => $this->user->id, 'company_id' => $this->company->id,
        ]);
        $this->assertFalse($schedule->isDue());
    }

    // ── §11.3 drill-down parity ──

    public function test_income_statement_drill_links_carry_branch_id(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.income-statement.index', [
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'branch_id' => $this->branch->id,
        ]));

        $response->assertOk();
        $response->assertSee("branch_id={$this->branch->id}");
    }

    public function test_balance_sheet_drill_links_carry_branch_id(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.balance-sheet.index', [
            'branch_id' => $this->branch->id,
        ]));

        $response->assertOk();
        $response->assertSee("branch_id={$this->branch->id}");
    }

    // ── §16.10 export blocking ──

    public function test_balance_sheet_csv_blocked_when_unbalanced(): void
    {
        // Create an asset account with an opening balance but no matching equity/liability
        // to make the balance sheet unbalanced
        $assetAcc2 = Account::create([
            'company_id' => $this->company->id, 'code' => '1500',
            'name' => 'Prepaid Insurance', 'type' => 'asset', 'sub_type' => 'current_asset',
            'is_active' => true, 'opening_balance' => 5000,
        ]);

        // Verify the BS is actually unbalanced by checking the service
        $service = app(\App\Services\Reporting\BalanceSheetService::class);
        $stmt = $service->generate($this->company->id, null, now()->format('Y-m-d'));
        $this->assertFalse($stmt['balanced'], 'Balance sheet should be unbalanced');

        // Export should redirect back with error
        $this->actingAs($this->user)->get(route('accounting.balance-sheet.export-csv'))
            ->assertRedirect();

        $this->actingAs($this->user)->get(route('accounting.balance-sheet.export-pdf'))
            ->assertRedirect();
    }

    public function test_cash_flow_csv_succeeds_when_no_mismatch(): void
    {
        // The test fixture has a balanced scenario where opening + net = closing
        // The mismatch check compares computed ending cash vs actual bank balance.
        // Use a date range BEFORE any JE activity to avoid mismatch.
        $response = $this->actingAs($this->user)->get(route('accounting.cash-flow.export-csv', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->subDays(20)->format('Y-m-d'),
        ]));
        // Before any JE activity, there should be no mismatch
        $response->assertOk();
    }

    // ── Guest redirect ──

    public function test_unauthenticated_redirects_to_login(): void
    {
        $this->get(route('accounting.income-statement.index'))
            ->assertRedirect('/login');

        $this->get(route('accounting.report-schedules.index'))
            ->assertRedirect('/login');
    }
}
