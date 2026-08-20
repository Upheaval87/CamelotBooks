<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\Employee;
use App\Models\EmployeePayslipSetting;
use App\Models\EmployeeSalaryStructure;
use App\Models\FiscalYear;
use App\Models\NumberingSequence;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Payslip;
use App\Models\PayslipDistribution;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayslipDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Employee $employee;
    protected PayrollRun $run;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'PDTCO', 'name' => 'Payslip Dist Test Co',
            'base_currency' => 'USD', 'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);
        FeatureManagement::enable($this->company->id, 'payroll');

        $this->seedDefaultAccounts();
        $this->seedEmployee();
        $this->seedNumberingSequences();
        $this->seedAccountingPeriods();

        $this->run = $this->createRun('approved');
        $this->addItem($this->run);
    }

    private function seedDefaultAccounts(): void
    {
        $accounts = [
            ['code' => '6000', 'name' => 'Salary Expense', 'type' => 'expense', 'sub_type' => 'salary_expense'],
            ['code' => '6010', 'name' => 'Pension Expense', 'type' => 'expense', 'sub_type' => 'pension_expense'],
            ['code' => '2400', 'name' => 'PAYE Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '2410', 'name' => 'Pension Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '2420', 'name' => 'Net Pay Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
            ['code' => '1400', 'name' => 'Loans Receivable', 'type' => 'asset', 'sub_type' => 'current_asset'],
            ['code' => '1000', 'name' => 'Bank Account', 'type' => 'asset', 'sub_type' => 'current_asset', 'is_bank_account' => true],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability'],
        ];
        $mappingKeys = [
            'salary_expense' => '6000', 'pension_expense' => '6010',
            'paye_payable' => '2400', 'pension_payable' => '2410',
            'net_pay_payable' => '2420', 'loan_receivable' => '1400',
            'default_bank' => '1000', 'accounts_payable' => '2000',
        ];
        foreach ($accounts as $a) {
            Account::create(array_merge($a, ['company_id' => $this->company->id, 'is_active' => true]));
        }
        foreach ($mappingKeys as $key => $code) {
            $account = Account::where('company_id', $this->company->id)->where('code', $code)->first();
            if ($account) {
                DefaultAccountMapping::create(['company_id' => $this->company->id, 'mapping_key' => $key, 'account_id' => $account->id]);
            }
        }
    }

    private function seedEmployee(): void
    {
        $this->employee = Employee::create([
            'company_id' => $this->company->id, 'employee_number' => 'EMP-00001',
            'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@test.com',
            'department' => 'Finance', 'job_title' => 'Accountant', 'hire_date' => now()->subYear(),
            'employment_status' => 'active', 'is_active' => true,
            'payslip_password' => Crypt::encryptString('TestPass1'),
        ]);
        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id,
            'basic_pay' => 4000, 'effective_from' => now()->subYear(), 'is_current' => true,
        ]);
    }

    private function seedNumberingSequences(): void
    {
        NumberingSequence::create([
            'company_id' => $this->company->id, 'document_type' => 'payroll_run',
            'prefix' => 'PR-', 'padding_width' => 4, 'next_number' => 1,
            'reset_policy' => 'monthly', 'is_active' => true,
        ]);
    }

    private function seedAccountingPeriods(): void
    {
        $fy = FiscalYear::create([
            'company_id' => $this->company->id, 'label' => 'FY 2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'open',
        ]);
        AccountingPeriod::create([
            'company_id' => $this->company->id, 'fiscal_year_id' => $fy->id,
            'label' => 'August 2026', 'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(), 'status' => 'open',
        ]);
    }

    private function createRun(string $status = 'approved'): PayrollRun
    {
        return PayrollRun::create([
            'company_id' => $this->company->id, 'run_number' => 'PR-202608-0001',
            'period_label' => 'August 2026', 'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(), 'pay_date' => now()->endOfMonth(),
            'status' => $status, 'created_by' => $this->user->id,
        ]);
    }

    private function addItem(PayrollRun $run): PayrollRunItem
    {
        return PayrollRunItem::create([
            'payroll_run_id' => $run->id, 'employee_id' => $this->employee->id,
            'basic_pay' => 4000, 'total_allowances' => 0, 'gross_pay' => 4000,
            'paye' => 0, 'pension_ee' => 0, 'total_deductions' => 0, 'net_pay' => 4000,
        ]);
    }

    private function makePayslip(string $status = 'draft'): Payslip
    {
        return Payslip::create([
            'company_id' => $this->company->id, 'payroll_run_id' => $this->run->id,
            'employee_id' => $this->employee->id, 'payslip_number' => 'PS-000001',
            'status' => $status, 'gross_pay' => 4000, 'total_deductions' => 0, 'net_pay' => 4000,
            'earnings' => [['item' => 'Basic Pay', 'basis' => 'Monthly', 'amount' => 4000]],
            'deductions' => [], 'employer_contributions' => [],
            'ytd_totals' => ['gross' => 4000, 'paye' => 0, 'pension' => 0],
            'generated_at' => now(),
        ]);
    }

    // ─── Generate ──────────────────────────────────

    public function test_generate_creates_payslips(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.generate', $this->run))
            ->assertRedirect();
        $this->assertDatabaseHas('payslips', [
            'company_id' => $this->company->id, 'payroll_run_id' => $this->run->id,
            'employee_id' => $this->employee->id, 'status' => 'draft',
        ]);
    }

    public function test_generate_skips_existing_payslips(): void
    {
        $this->makePayslip();
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.generate', $this->run))
            ->assertRedirect();
        $this->assertEquals(1, Payslip::where('payroll_run_id', $this->run->id)->count());
    }

    // ─── Finalize ──────────────────────────────────

    public function test_finalize_locks_draft_payslips(): void
    {
        $payslip = $this->makePayslip();
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.finalize', $this->run))
            ->assertRedirect();
        $payslip->refresh();
        $this->assertEquals('finalized', $payslip->status);
        $this->assertNotNull($payslip->finalized_at);
    }

    // ─── Validate ──────────────────────────────────

    public function test_validate_renders_when_all_valid(): void
    {
        $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.validate', $this->run))
            ->assertOk()->assertSee('ready for distribution');
    }

    public function test_validate_shows_issues_for_missing_email(): void
    {
        $this->employee->update(['email' => null]);
        $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.validate', $this->run))
            ->assertOk()->assertSee('No email address on file');
    }

    public function test_validate_shows_issues_when_email_disabled(): void
    {
        EmployeePayslipSetting::create([
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id,
            'email_delivery' => false, 'portal_access' => true,
        ]);
        $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.validate', $this->run))
            ->assertOk()->assertSee('Email delivery disabled');
    }

    // ─── Send single ───────────────────────────────

    public function test_send_payslip_queues_mail(): void
    {
        Mail::fake();
        $payslip = $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.send', $payslip))
            ->assertRedirect();
        $payslip->refresh();
        $this->assertEquals('sent', $payslip->status);
        $this->assertDatabaseHas('payslip_distributions', [
            'payslip_id' => $payslip->id, 'status' => 'sent', 'channel' => 'email',
        ]);
    }

    public function test_send_records_audit_log(): void
    {
        Mail::fake();
        $payslip = $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.send', $payslip));
        $this->assertDatabaseHas('payslip_audit_logs', [
            'company_id' => $this->company->id, 'payslip_id' => $payslip->id,
            'employee_id' => $this->employee->id, 'action' => 'sent',
        ]);
    }

    // ─── Bulk send ─────────────────────────────────

    public function test_bulk_send_dispatches_to_all_finalized(): void
    {
        Mail::fake();
        $payslip = $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.bulk-send', $this->run))
            ->assertRedirect();
        $payslip->refresh();
        $this->assertEquals('sent', $payslip->status);
    }

    public function test_bulk_send_skips_when_email_disabled(): void
    {
        Mail::fake();
        EmployeePayslipSetting::create([
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id,
            'email_delivery' => false, 'portal_access' => true,
        ]);
        $payslip = $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.bulk-send', $this->run))
            ->assertRedirect();
        $payslip->refresh();
        $this->assertEquals('finalized', $payslip->status);
    }

    // ─── Resend ────────────────────────────────────

    public function test_resend_failed_distribution(): void
    {
        Mail::fake();
        $payslip = $this->makePayslip('finalized');
        $distribution = PayslipDistribution::create([
            'company_id' => $this->company->id, 'payslip_id' => $payslip->id,
            'employee_id' => $this->employee->id, 'channel' => 'email',
            'status' => 'failed', 'email_address' => 'jane@test.com',
            'error_message' => 'Connection timeout', 'retry_count' => 0,
        ]);
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.resend', $distribution))
            ->assertRedirect();
        $distribution->refresh();
        $this->assertEquals('sent', $distribution->status);
        $this->assertEquals(1, $distribution->retry_count);
        $this->assertNotNull($distribution->sent_at);
    }

    // ─── Status ────────────────────────────────────

    public function test_status_renders(): void
    {
        $this->makePayslip('finalized');
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.status', $this->run))
            ->assertOk()->assertSee('Distribution Status')->assertSee('Jane');
    }

    // ─── Finalized CTA ─────────────────────────────

    public function test_finalized_page_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.finalized', $this->run))
            ->assertOk()->assertSee('Distribution')->assertSee('PR-202608-0001');
    }

    // ─── Employee settings ─────────────────────────

    public function test_employee_settings_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.employee-settings'))
            ->assertOk()->assertSee('Jane')->assertSee('Employee Payslip Settings');
    }

    public function test_update_employee_settings_persists(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.update-employee-settings'), [
                'employee_id' => $this->employee->id, 'email_delivery' => 1,
                'portal_access' => 1, 'custom_email' => 'jane-payroll@test.com',
            ])->assertRedirect();
        $this->assertDatabaseHas('employee_payslip_settings', [
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id,
            'email_delivery' => true, 'portal_access' => true,
            'custom_email' => 'jane-payroll@test.com',
        ]);
    }

    public function test_update_employee_settings_with_invalid_email(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.distribution.update-employee-settings'), [
                'employee_id' => $this->employee->id, 'email_delivery' => 1,
                'portal_access' => 1, 'custom_email' => 'not-an-email',
            ])->assertSessionHasErrors('custom_email');
    }

    // ─── Audit trail ───────────────────────────────

    public function test_audit_trail_renders(): void
    {
        $this->makePayslip();
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.audit'))
            ->assertOk()->assertSee('Distribution Audit Trail')->assertSee('generated');
    }

    public function test_audit_trail_filters_by_action(): void
    {
        $this->makePayslip();
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.audit', ['action' => 'generated']))
            ->assertOk()->assertSee('generated');
    }

    public function test_export_audit_csv(): void
    {
        $this->makePayslip();
        $response = $this->actingAs($this->user)
            ->get(route('accounting.payroll.distribution.audit.export'));
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    // ─── Cross-company isolation ───────────────────

    public function test_cross_company_404(): void
    {
        $otherCompany = Company::create([
            'company_code' => 'OTHER', 'name' => 'Other Co',
            'base_currency' => 'USD', 'fiscal_year_start_month' => 1,
        ]);
        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($otherCompany->id, ['role' => 'company_admin']);
        setPermissionsTeamId($otherCompany->id);
        $otherUser->assignRole('company_admin');

        $this->actingAs($otherUser);
        session(['current_company_id' => $otherCompany->id]);

        $this->get(route('accounting.payroll.distribution.finalized', $this->run))
            ->assertNotFound();
    }

    // ─── Portal: login renders ─────────────────────

    public function test_portal_login_renders(): void
    {
        session(['current_company_id' => $this->company->id]);
        $this->get(route('accounting.payroll.portal.login'))
            ->assertOk()->assertSee('Sign In')->assertSee('Employee Number');
    }

    // ─── Portal: authenticate ──────────────────────

    public function test_portal_authenticate_success(): void
    {
        session(['current_company_id' => $this->company->id]);
        $this->post(route('accounting.payroll.portal.authenticate'), [
            'employee_number' => 'EMP-00001', 'password' => 'TestPass1',
        ])->assertRedirect(route('accounting.payroll.portal.index'));
        $this->assertEquals($this->employee->id, session('portal_employee_id'));
        $this->assertEquals($this->company->id, session('portal_company_id'));
    }

    public function test_portal_authenticate_wrong_password(): void
    {
        session(['current_company_id' => $this->company->id]);
        $this->post(route('accounting.payroll.portal.authenticate'), [
            'employee_number' => 'EMP-00001', 'password' => 'WrongPassword',
        ])->assertRedirect()->assertSessionHasErrors('employee_number');
        $this->assertNull(session('portal_employee_id'));
    }

    public function test_portal_authenticate_nonexistent_employee(): void
    {
        session(['current_company_id' => $this->company->id]);
        $this->post(route('accounting.payroll.portal.authenticate'), [
            'employee_number' => 'EMP-99999', 'password' => 'anything',
        ])->assertRedirect()->assertSessionHasErrors('employee_number');
    }

    // ─── Portal: index ─────────────────────────────

    public function test_portal_index_renders_payslips(): void
    {
        session(['current_company_id' => $this->company->id]);
        session(['portal_employee_id' => $this->employee->id]);
        session(['portal_company_id' => $this->company->id]);
        $payslip = $this->makePayslip('finalized');

        $this->get(route('accounting.payroll.portal.index'))
            ->assertOk()->assertSee('My Payslips')->assertSee('PS-000001');
    }

    public function test_portal_index_403_without_session(): void
    {
        $this->get(route('accounting.payroll.portal.index'))->assertStatus(403);
    }

    // ─── Portal: preview ───────────────────────────

    public function test_portal_preview_renders_payslip(): void
    {
        session(['current_company_id' => $this->company->id]);
        session(['portal_employee_id' => $this->employee->id]);
        session(['portal_company_id' => $this->company->id]);
        $payslip = $this->makePayslip('finalized');

        $this->get(route('accounting.payroll.portal.preview', $payslip))
            ->assertOk()->assertSee('PS-000001')->assertSee('Basic Pay');
    }

    public function test_portal_preview_403_for_other_employees_payslip(): void
    {
        session(['current_company_id' => $this->company->id]);
        session(['portal_company_id' => $this->company->id]);
        $otherEmp = Employee::create([
            'company_id' => $this->company->id, 'employee_number' => 'EMP-00002',
            'first_name' => 'Bob', 'last_name' => 'Jones', 'email' => 'bob@test.com',
            'department' => 'IT', 'hire_date' => now()->subYear(),
            'employment_status' => 'active', 'is_active' => true,
        ]);
        session(['portal_employee_id' => $otherEmp->id]);
        $payslip = $this->makePayslip('finalized');

        $this->get(route('accounting.payroll.portal.preview', $payslip))
            ->assertStatus(403);
    }

    public function test_portal_preview_404_for_draft_payslip(): void
    {
        session(['current_company_id' => $this->company->id]);
        session(['portal_employee_id' => $this->employee->id]);
        session(['portal_company_id' => $this->company->id]);
        $payslip = $this->makePayslip('draft');

        $this->get(route('accounting.payroll.portal.preview', $payslip))
            ->assertNotFound();
    }

    // ─── Portal: logout ────────────────────────────

    public function test_portal_logout_clears_session(): void
    {
        session(['current_company_id' => $this->company->id]);
        session(['portal_employee_id' => $this->employee->id]);
        session(['portal_company_id' => $this->company->id]);

        $this->post(route('accounting.payroll.portal.logout'))
            ->assertRedirect(route('accounting.payroll.portal.login'));
        $this->assertNull(session('portal_employee_id'));
        $this->assertNull(session('portal_company_id'));
    }
}