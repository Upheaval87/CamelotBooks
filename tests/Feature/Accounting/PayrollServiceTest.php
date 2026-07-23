<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\JournalEntry;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PayrollRun;
use App\Models\PensionScheme;
use App\Models\User;
use App\Services\Accounting\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
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

    // ── Post Payroll Tests ─────────────────────────────────

    public function test_post_payroll_creates_journal_entry(): void
    {
        $this->createEmployees(2);

        $run = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

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

    public function test_generate_run_number_is_sequential(): void
    {
        $run1 = $this->service->runPayroll(
            $this->company->id, 'June 2026', '2026-06-30', '2026-06-01', '2026-06-30', $this->user->id
        );
        $run2 = $this->service->runPayroll(
            $this->company->id, 'July 2026', '2026-07-31', '2026-07-01', '2026-07-31', $this->user->id
        );

        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{2}-\d{3}$/', $run1->run_number);
        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{2}-\d{3}$/', $run2->run_number);
        $this->assertNotEquals($run1->run_number, $run2->run_number);
    }
}
