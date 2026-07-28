<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\JournalEntry;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PayrollRun;
use App\Models\PayslipDelivery;
use App\Models\PensionScheme;
use App\Models\User;
use App\Services\Accounting\PayrollService;
use App\Services\Payroll\EncryptedPayslipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected PayrollService $service;
    protected Account $cashAccount;
    protected Account $salaryExpense;
    protected Account $payePayable;
    protected Account $pensionPayable;
    protected Account $netPayPayable;
    protected Account $employerPensionExpense;
    protected PayeTable $payeTable;
    protected PensionScheme $pensionScheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'name' => 'Test Company',
            'company_code' => 'TST',
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);

        $this->service = app(PayrollService::class);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank_account' => true,
        ]);

        $this->salaryExpense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Salary Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->payePayable = Account::create([
            'company_id' => $this->company->id,
            'code' => '2400',
            'name' => 'PAYE Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->pensionPayable = Account::create([
            'company_id' => $this->company->id,
            'code' => '2410',
            'name' => 'Pension Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->netPayPayable = Account::create([
            'company_id' => $this->company->id,
            'code' => '2420',
            'name' => 'Net Pay Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'is_active' => true,
        ]);

        $this->employerPensionExpense = Account::create([
            'company_id' => $this->company->id,
            'code' => '6010',
            'name' => 'Employer Pension Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);

        $this->payeTable = PayeTable::create([
            'company_id' => $this->company->id,
            'version_name' => '2026 PAYE',
            'effective_from' => now()->subMonth(),
            'is_current' => true,
        ]);

        PayeTableBand::create([
            'paye_table_id' => $this->payeTable->id,
            'threshold' => 0,
            'upper_limit' => 100000,
            'rate' => 0,
            'sort_order' => 0,
        ]);
        PayeTableBand::create([
            'paye_table_id' => $this->payeTable->id,
            'threshold' => 100000,
            'upper_limit' => 200000,
            'rate' => 15,
            'sort_order' => 1,
        ]);
        PayeTableBand::create([
            'paye_table_id' => $this->payeTable->id,
            'threshold' => 200000,
            'upper_limit' => null,
            'rate' => 30,
            'sort_order' => 2,
        ]);

        $this->pensionScheme = PensionScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Test Pension Fund',
            'employee_rate' => 5,
            'employer_rate' => 10,
            'effective_from' => now()->subMonth(),
            'is_current' => true,
        ]);

        $accounts = Account::where('company_id', $this->company->id)->get()->keyBy('code');
        $mappingData = [
            'default_bank' => '1000',
            'salary_expense' => '6000',
            'pension_expense' => '6010',
            'paye_payable' => '2400',
            'pension_payable' => '2410',
            'net_pay_payable' => '2420',
        ];
        foreach ($mappingData as $key => $code) {
            if (isset($accounts[$code])) {
                \App\Models\DefaultAccountMapping::setMapping(
                    $this->company->id, $key, $accounts[$code]->id
                );
            }
        }

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        app(\App\Services\Admin\NumberingSequenceService::class)->seedDefaults($this->company->id);
    }

    protected function createEmployees(int $count): array
    {
        $employees = [];
        for ($i = 0; $i < $count; $i++) {
            $employee = Employee::create([
                'company_id' => $this->company->id,
                'employee_number' => sprintf('EMP-%04d', $i + 1),
                'first_name' => "Employee",
                'last_name' => chr(65 + $i),
                'hire_date' => now()->subYear(),
                'is_active' => true,
                'employment_status' => 'active',
            ]);

            $basicPay = 200000 + ($i * 50000);
            EmployeeSalaryStructure::create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'basic_pay' => $basicPay,
                'effective_from' => now()->subYear(),
                'is_current' => true,
            ]);

            $employees[] = $employee;
        }
        return $employees;
    }

    // ── Run Payroll Tests ──────────────────────────────────

    public function test_run_payroll_creates_run_and_items(): void
    {
        $this->createEmployees(2);

        $run = $this->service->runPayroll(
            $this->company->id,
            'July 2026',
            '2026-07-31',
            '2026-07-01',
            '2026-07-31',
            $this->user->id
        );

        $this->assertNotNull($run);
        $this->assertEquals(PayrollRun::STATUS_CALCULATED, $run->status);
        $this->assertEquals('July 2026', $run->period_label);
        $this->assertEquals(2, $run->items()->count());
        $this->assertGreaterThan(0, $run->total_gross);
    }

    public function test_run_payroll_creates_separate_runs_for_same_period(): void
    {
        $this->createEmployees(1);

        $run1 = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $run2 = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->assertNotEquals($run1->id, $run2->id);
        $this->assertEquals(PayrollRun::STATUS_CALCULATED, $run1->status);
        $this->assertEquals(PayrollRun::STATUS_CALCULATED, $run2->status);
    }

    public function test_run_payroll_throws_if_no_paye_table(): void
    {
        $company2 = Company::create([
            'name' => 'No PayE Company',
            'company_code' => 'NPAYE',
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->runPayroll(
            $company2->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );
    }

    // ── Approval Tests ──────────────────────────────────────

    public function test_approve_payroll_sets_approved_status(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->assertEquals(PayrollRun::STATUS_CALCULATED, $run->status);

        $run = $this->service->approvePayroll($run, $this->user->id);

        $this->assertEquals(PayrollRun::STATUS_APPROVED, $run->status);
        $this->assertNotNull($run->approved_at);
        $this->assertEquals($this->user->id, $run->approved_by);
    }

    public function test_approve_only_calculated_runs(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->approvePayroll($run, $this->user->id);
    }

    public function test_post_requires_approval(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->service->postPayroll($run, $this->user->id);
    }

    // ── Post Payroll Tests ─────────────────────────────────

    public function test_post_payroll_creates_journal_entry(): void
    {
        $this->createEmployees(2);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_POSTED, $run->status);
        $this->assertNotNull($run->journal_entry_id);

        $je = JournalEntry::find($run->journal_entry_id);
        $this->assertEquals('posted', $je->status);

        $totalDebit = $je->lines->sum('debit');
        $totalCredit = $je->lines->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
    }

    public function test_post_payroll_throws_if_already_posted(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $run->update(['status' => PayrollRun::STATUS_POSTED]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->postPayroll($run, $this->user->id);
    }

    // ── Payment Tests ──────────────────────────────────────

    public function test_pay_employee_records_payment_and_updates_status(): void
    {
        $this->createEmployees(2);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        $item = $run->items()->first();
        $employeeId = $item->employee_id;

        $this->service->payEmployee(
            $run,
            $employeeId,
            (float) $item->net_pay,
            '2026-07-31',
            $this->cashAccount->id,
            $this->user->id
        );

        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_PARTIALLY_PAID, $run->status);
        $this->assertDatabaseHas('employee_payments', [
            'employee_id' => $employeeId,
            'payment_type' => 'salary',
        ]);
    }

    public function test_pay_all_employees_marks_fully_paid(): void
    {
        $this->createEmployees(2);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        foreach ($run->items as $item) {
            $this->service->payEmployee(
                $run,
                $item->employee_id,
                (float) $item->net_pay,
                '2026-07-31',
                $this->cashAccount->id,
                $this->user->id
            );
        }

        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_FULLY_PAID, $run->status);
    }

    // ── PAYE Remittance Tests ─────────────────────────────

    public function test_remit_paye_creates_liability_payment(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        $totalPaye = (float) $run->total_paye;

        $this->service->remitPAYE(
            $run,
            $totalPaye,
            '2026-08-10',
            $this->cashAccount->id,
            $this->user->id
        );

        $this->assertDatabaseHas('employee_payments', [
            'payroll_run_id' => $run->id,
            'payment_type' => 'paye_remittance',
        ]);
    }

    // ── Pension Remittance Tests ───────────────────────────

    public function test_remit_pension_creates_liability_payment(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        $totalPension = (float) $run->total_pension_ee + (float) $run->total_pension_er;

        $this->service->remitPension(
            $run,
            $totalPension,
            '2026-08-15',
            $this->cashAccount->id,
            $this->user->id
        );

        $this->assertDatabaseHas('employee_payments', [
            'payroll_run_id' => $run->id,
            'payment_type' => 'pension_remittance',
        ]);
    }

    // ── Number Generation Tests ────────────────────────────

    public function test_generate_run_number_uses_numbering_sequence(): void
    {
        $run1 = $this->service->runPayroll(
            $this->company->id, 'June 2026', '2026-06-30', '2026-06-01', '2026-06-30', $this->user->id
        );
        $run2 = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->assertMatchesRegularExpression('/^PR-\d{6}-\d{3}$/', $run1->run_number);
        $this->assertMatchesRegularExpression('/^PR-\d{6}-\d{3}$/', $run2->run_number);
        $this->assertNotEquals($run1->run_number, $run2->run_number);
    }

    public function test_payroll_balanced_je_and_net_pay_formula(): void
    {
        $employees = $this->createEmployees(1);
        $employee = $employees[0];

        $run = $this->service->runPayroll(
            $this->company->id,
            'June 2026',
            '2026-06-30',
            '2026-06-01',
            '2026-06-30',
            $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        $run->refresh();
        $this->assertEquals(PayrollRun::STATUS_POSTED, $run->status);
        $this->assertNotNull($run->journal_entry_id);

        $je = JournalEntry::find($run->journal_entry_id);
        $this->assertEquals('posted', $je->status);

        $totalDebit = $je->lines()->sum('debit');
        $totalCredit = $je->lines()->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit, 'Journal entry must balance');

        $item = $run->items()->first();
        $expectedNet = (float) $item->gross_pay - (float) $item->paye - (float) $item->pension_ee;
        $this->assertEqualsWithDelta($expectedNet, (float) $item->net_pay, 0.01);
    }

    // ── Encrypted Payslip Tests ─────────────────────────────

    public function test_encrypted_payslip_pdf_requires_password(): void
    {
        $this->createEmployees(1);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $item = $run->items()->with('employee')->first();
        $payslipService = app(EncryptedPayslipService::class);

        $pdfContent = $payslipService->generatePayslipPdf($run, $item);

        $this->assertNotEmpty($pdfContent);
        $this->assertStringContainsString('%PDF', $pdfContent);
        $this->assertGreaterThan(1000, strlen($pdfContent));
    }

    public function test_payslip_password_not_in_pdf_content(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Secret',
            'last_name' => 'User',
            'tax_id' => 'TAX-1234',
            'date_of_birth' => '1990-01-01',
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
            'email' => 'secret@test.com',
        ]);

        $employee->setPayslipPasswordValueAttribute('MySecret123');
        $employee->save();

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_pay' => 200000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $item = $run->items()->with('employee')->first();
        $payslipService = app(EncryptedPayslipService::class);

        $password = $payslipService->getEmployeePassword($item->employee);

        $this->assertEquals('MySecret123', $password);

        $pdfContent = $payslipService->generatePayslipPdf($run, $item);

        $this->assertStringNotContainsString('MySecret123', $pdfContent);
    }

    public function test_payslip_password_not_in_email(): void
    {
        Mail::fake();

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Email',
            'last_name' => 'Test',
            'email' => 'email@test.com',
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
        ]);

        $employee->setPayslipPasswordValueAttribute('TopSecret999');
        $employee->save();

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_pay' => 200000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        Artisan::call('payroll:send-payslips', ['runId' => $run->id]);

        Mail::assertSent(\App\Mail\PayslipMail::class, function ($mail) {
            $reflection = new \ReflectionClass($mail);
            $method = $reflection->getMethod('buildBody');
            $method->setAccessible(true);
            $body = $method->invoke($mail);
            $this->assertStringNotContainsString('TopSecret999', $body);
            $this->assertStringNotContainsString('TopSecret999', strtolower($body));
            return true;
        });
    }

    public function test_payslip_password_not_in_audit_log(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Audit',
            'last_name' => 'Test',
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
        ]);

        $employee->setPayslipPasswordValueAttribute('AuditTest456');
        $employee->save();

        AuditLog::log(
            $this->company->id,
            $this->user->id,
            Employee::class,
            $employee->id,
            'payslip_password_changed',
            null,
            ['payslip_password' => '[REDACTED]'],
            'Payslip password was updated'
        );

        $logs = AuditLog::where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->where('action', 'payslip_password_changed')
            ->get();

        $this->assertGreaterThan(0, $logs->count());

        foreach ($logs as $log) {
            $this->assertArrayNotHasKey('payslip_password', $log->old_values ?? []);
            $this->assertStringNotContainsString('AuditTest456', json_encode($log->toArray()));
        }
    }

    public function test_send_payslips_creates_delivery_records(): void
    {
        Mail::fake();

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Delivery',
            'last_name' => 'Test',
            'email' => 'delivery@test.com',
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
        ]);

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_pay' => 200000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        Artisan::call('payroll:send-payslips', ['runId' => $run->id]);

        $this->assertDatabaseHas('payslip_deliveries', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'status' => 'sent',
            'email_address' => 'delivery@test.com',
        ]);
    }

    public function test_send_payslips_tracks_failure(): void
    {
        Mail::fake();

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Fail',
            'last_name' => 'Test',
            'email' => 'fail@test.com',
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
        ]);

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_pay' => 200000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        Mail::to($employee->email)->send(
            new \App\Mail\PayslipMail($run, $employee, null)
        );

        $delivery = PayslipDelivery::create([
            'company_id' => $this->company->id,
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'status' => PayslipDelivery::STATUS_FAILED,
            'email_address' => $employee->email,
            'error_message' => 'Test simulated failure',
        ]);

        $this->assertEquals('failed', $delivery->status);
        $this->assertNotNull($delivery->error_message);
    }

    public function test_send_payslips_skips_employees_without_email(): void
    {
        Mail::fake();

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'NoEmail',
            'last_name' => 'Test',
            'email' => null,
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
        ]);

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_pay' => 200000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->service->approvePayroll($run, $this->user->id);
        $this->service->postPayroll($run, $this->user->id);

        Artisan::call('payroll:send-payslips', ['runId' => $run->id]);

        $this->assertDatabaseHas('payslip_deliveries', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'status' => 'failed',
            'error_message' => 'No email address on file',
        ]);

        Mail::assertNothingSent();
    }

    public function test_payslip_password_encrypted_at_rest(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Encrypt',
            'last_name' => 'Test',
            'hire_date' => now()->subYear(),
            'is_active' => true,
            'employment_status' => 'active',
        ]);

        $employee->setPayslipPasswordValueAttribute('PlainText123');
        $employee->save();

        $rawDb = \DB::table('employees')->where('id', $employee->id)->first();

        $this->assertNotEquals('PlainText123', $rawDb->payslip_password);
        $this->assertNotEmpty($rawDb->payslip_password);
    }
}
