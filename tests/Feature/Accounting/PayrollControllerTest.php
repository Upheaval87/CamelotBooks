<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryStructure;
use App\Models\FiscalYear;
use App\Models\NumberingSequence;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'CTLCO',
            'name' => 'Controller Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
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
            'salary_expense'  => '6000',
            'pension_expense' => '6010',
            'paye_payable'    => '2400',
            'pension_payable' => '2410',
            'net_pay_payable' => '2420',
            'loan_receivable' => '1400',
            'default_bank'    => '1000',
            'accounts_payable' => '2000',
        ];

        foreach ($accounts as $a) {
            Account::create(array_merge($a, [
                'company_id' => $this->company->id,
                'is_active' => true,
            ]));
        }

        foreach ($mappingKeys as $key => $code) {
            $account = Account::where('company_id', $this->company->id)->where('code', $code)->first();
            if ($account) {
                DefaultAccountMapping::create([
                    'company_id' => $this->company->id,
                    'mapping_key' => $key,
                    'account_id' => $account->id,
                ]);
            }
        }
    }

    private function seedEmployee(): void
    {
        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP-00001',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@ctest.com',
            'department' => 'Finance',
            'job_title' => 'Accountant',
            'hire_date' => now()->subYear(),
            'employment_status' => 'active',
            'is_active' => true,
        ]);

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_pay' => 4000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);
    }

    private function seedNumberingSequences(): void
    {
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'payroll_run',
            'prefix' => 'PR-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'monthly',
            'is_active' => true,
        ]);
        NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'employee_payment',
            'prefix' => 'EP-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'annually',
            'is_active' => true,
        ]);
    }

    private function seedAccountingPeriods(): void
    {
        $fy = FiscalYear::create([
            'company_id' => $this->company->id,
            'label' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'fiscal_year_id' => $fy->id,
            'label' => 'August 2026',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ]);
    }

    private function createRun(string $status = 'draft'): PayrollRun
    {
        return PayrollRun::create([
            'company_id' => $this->company->id,
            'run_number' => 'PR-202608-0001',
            'period_label' => 'August 2026',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'pay_date' => now()->endOfMonth(),
            'status' => $status,
            'created_by' => $this->user->id,
        ]);
    }

    private function addItem(PayrollRun $run): PayrollRunItem
    {
        return PayrollRunItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employee->id,
            'basic_pay' => 4000,
            'total_allowances' => 0,
            'gross_pay' => 4000,
            'paye' => 0,
            'pension_ee' => 0,
            'total_deductions' => 0,
            'net_pay' => 4000,
        ]);
    }

    // ─── Dashboard ──────────────────────────────

    public function test_dashboard_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.dashboard'))
            ->assertOk()
            ->assertSee('Payroll Centre');
    }

    // ─── Employees ──────────────────────────────

    public function test_employees_index_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.employees.index'))
            ->assertOk()
            ->assertSee('Jane');
    }

    public function test_employees_create_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.employees.create'))
            ->assertOk()
            ->assertSee('Onboard a new employee');
    }

    public function test_employees_store_persists(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.employees.store'), [
                'first_name' => 'Alice',
                'last_name' => 'Wonder',
                'email' => 'alice@test.com',
                'hire_date' => now()->format('Y-m-d'),
                'basic_salary' => 3000,
                'payment_frequency' => 'monthly',
            ])->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'email' => 'alice@test.com',
            'employment_status' => 'active',
        ]);

        $emp = Employee::where('email', 'alice@test.com')->first();
        $this->assertDatabaseHas('employee_salary_structures', [
            'employee_id' => $emp->id,
            'basic_pay' => 3000,
            'is_current' => true,
        ]);
    }

    public function test_employees_show_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.employees.show', $this->employee))
            ->assertOk()
            ->assertSee('Jane');
    }

    public function test_employees_edit_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.employees.edit', $this->employee))
            ->assertOk()
            ->assertSee('Edit Employee');
    }

    public function test_employees_update_persists(): void
    {
        $this->actingAs($this->user)
            ->put(route('accounting.payroll.employees.update', $this->employee), [
                'first_name' => 'Janet',
                'last_name' => 'Smith',
                'email' => 'janet@ctest.com',
                'hire_date' => $this->employee->hire_date->format('Y-m-d'),
                'basic_salary' => 4500,
                'payment_frequency' => 'monthly',
            ])->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'first_name' => 'Janet',
        ]);

        $structure = $this->employee->currentSalaryStructure;
        $this->assertEquals(4500, (float) $structure->fresh()->basic_pay);
    }

    // ─── Runs ───────────────────────────────────

    public function test_runs_index_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.runs.index'))
            ->assertOk()
            ->assertSee('Payroll Runs');
    }

    public function test_runs_create_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.runs.create'))
            ->assertOk()
            ->assertSee('New Payroll Run');
    }

    public function test_store_run_creates_run_with_items(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.runs.store'), [
                'pay_period_start' => now()->startOfMonth()->format('Y-m-d'),
                'pay_period_end' => now()->endOfMonth()->format('Y-m-d'),
                'payment_date' => now()->endOfMonth()->format('Y-m-d'),
                'employee_ids' => [$this->employee->id],
            ])->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $run = PayrollRun::where('company_id', $this->company->id)->first();
        $this->assertNotNull($run->run_number);
        $this->assertNotNull($run->period_label);
        $this->assertDatabaseHas('payroll_run_items', [
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_calculate_run_wires_to_service(): void
    {
        $run = $this->createRun('draft');
        PayrollRunItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('accounting.payroll.runs.calculate', $run))
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $run->id,
            'status' => 'calculated',
        ]);

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $this->assertEquals(4000, (float) $item->gross_pay);
    }

    public function test_submit_run_sets_pending_approval(): void
    {
        $run = $this->createRun('calculated');
        $this->addItem($run);

        $this->actingAs($this->user)
            ->post(route('accounting.payroll.runs.submit', $run))
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $run->id,
            'status' => 'pending_approval',
        ]);
    }

    public function test_approve_run_sets_approved(): void
    {
        $run = $this->createRun('pending_approval');
        $this->addItem($run);

        $this->actingAs($this->user)
            ->post(route('accounting.payroll.runs.approve', $run))
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $run->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    }

    public function test_post_run_wires_to_service(): void
    {
        $run = $this->createRun('approved');
        $this->addItem($run);

        $this->actingAs($this->user)
            ->post(route('accounting.payroll.runs.post', $run))
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $run->id,
            'status' => 'posted',
        ]);
    }

    public function test_show_run_renders(): void
    {
        $run = $this->createRun('draft');
        $this->addItem($run);

        $this->actingAs($this->user)
            ->get(route('accounting.payroll.runs.show', $run))
            ->assertOk()
            ->assertSee('PR-202608');
    }

    // ─── Payslips ───────────────────────────────

    public function test_payslips_index_renders(): void
    {
        $run = $this->createRun('calculated');
        $this->addItem($run);

        $this->actingAs($this->user)
            ->get(route('accounting.payroll.payslips.index'))
            ->assertOk()
            ->assertSee('Payslips');
    }

    public function test_show_payslip_renders(): void
    {
        $run = $this->createRun('calculated');
        $item = $this->addItem($run);

        $this->actingAs($this->user)
            ->get(route('accounting.payroll.payslips.show', $item))
            ->assertOk()
            ->assertSee('Jane');
    }

    // ─── Statutory ──────────────────────────────

    public function test_statutory_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.statutory.index'))
            ->assertOk()
            ->assertSee('Statutory');
    }

    public function test_store_paye_table(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.statutory.store'), [
                'type' => 'paye_table',
                'name' => '2026 Tax Table',
                'effective_from' => now()->startOfYear()->format('Y-m-d'),
                'bands' => [
                    ['threshold' => 0, 'upper_limit' => 4000, 'rate' => 0],
                    ['threshold' => 4000, 'upper_limit' => null, 'rate' => 25],
                ],
            ])->assertRedirect();

        $this->assertDatabaseHas('paye_tables', [
            'company_id' => $this->company->id,
            'version_name' => '2026 Tax Table',
        ]);
    }

    public function test_store_pension_scheme(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.statutory.store'), [
                'type' => 'pension_scheme',
                'name' => 'Company Pension',
                'employee_rate' => 5,
                'employer_rate' => 10,
                'effective_from' => now()->startOfYear()->format('Y-m-d'),
            ])->assertRedirect();

        $this->assertDatabaseHas('pension_schemes', [
            'company_id' => $this->company->id,
            'name' => 'Company Pension',
            'employee_rate' => 5,
            'employer_rate' => 10,
        ]);
    }

    // ─── People Ops ─────────────────────────────

    public function test_people_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.people.index'))
            ->assertOk()
            ->assertSee('People');
    }

    public function test_store_loan(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounting.payroll.people.store'), [
                'employee_id' => $this->employee->id,
                'amount' => 12000,
                'monthly_repayment' => 500,
                'interest_rate' => 5,
                'start_date' => now()->format('Y-m-d'),
            ])->assertRedirect();

        $this->assertDatabaseHas('employee_loans', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'principal_amount' => 12000,
            'monthly_deduction' => 500,
            'status' => 'active',
        ]);
    }

    // ─── Reports & Settings ─────────────────────

    public function test_reports_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.reports.index'))
            ->assertOk()
            ->assertSee('Reports');
    }

    public function test_settings_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounting.payroll.settings.index'))
            ->assertOk()
            ->assertSee('Settings');
    }

    // ─── Cross-company isolation ────────────────

    public function test_other_company_run_404s(): void
    {
        $otherCompany = Company::create([
            'company_code' => 'OTHER',
            'name' => 'Other Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);

        $run = PayrollRun::create([
            'company_id' => $otherCompany->id,
            'run_number' => 'PR-202608-9999',
            'period_label' => 'Other',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'pay_date' => now()->endOfMonth(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('accounting.payroll.runs.show', $run))
            ->assertStatus(404);
    }
}
