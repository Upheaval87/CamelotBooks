<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyAllowance;
use App\Models\DefaultAccountMapping;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryItem;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PensionScheme;
use App\Models\User;
use App\Services\FeatureManagement;
use App\Services\Payroll\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected PayrollRun $run;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'PAYCO',
            'name' => 'Payroll Test Co',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        $this->seedDefaultAccounts();
        $this->seedEmployee();
        $this->seedRun();
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
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'department' => 'Engineering',
            'job_title' => 'Senior Developer',
            'hire_date' => now()->subYear(),
            'employment_status' => 'active',
            'is_active' => true,
        ]);

        EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_pay' => 5000,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);

        $allowance = CompanyAllowance::create([
            'company_id' => $this->company->id,
            'name' => 'Housing Allowance',
            'code' => 'HOUSING',
            'type' => 'allowance',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        EmployeeSalaryItem::create([
            'salary_structure_id' => $this->employee->currentSalaryStructure->id,
            'company_allowance_id' => $allowance->id,
            'name' => 'Housing Allowance',
            'type' => 'fixed',
            'amount' => 1000,
        ]);
    }

    private function seedRun(): void
    {
        $this->run = PayrollRun::create([
            'company_id' => $this->company->id,
            'run_number' => 'PR-202608-0001',
            'period_label' => 'August 2026',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'pay_date' => now()->endOfMonth(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        PayrollRunItem::create([
            'payroll_run_id' => $this->run->id,
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_calculate_populates_items_with_basic_allowances_gross(): void
    {
        $service = app(PayrollService::class);
        $run = $service->calculate($this->run);

        $this->assertDatabaseHas('payroll_runs', [
            'id' => $run->id,
            'status' => 'calculated',
        ]);

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(5000, (float) $item->basic_pay);
        $this->assertEquals(1000, (float) $item->total_allowances);
        $this->assertEquals(6000, (float) $item->gross_pay);
    }

    public function test_calculate_applies_paye_when_table_exists(): void
    {
        $table = PayeTable::create([
            'company_id' => $this->company->id,
            'version_name' => '2026 Tax Table',
            'effective_from' => now()->startOfYear(),
            'is_current' => true,
        ]);

        PayeTableBand::create([
            'paye_table_id' => $table->id,
            'threshold' => 0,
            'upper_limit' => 4000,
            'rate' => 0,
            'sort_order' => 1,
        ]);
        PayeTableBand::create([
            'paye_table_id' => $table->id,
            'threshold' => 4000,
            'upper_limit' => null,
            'rate' => 25,
            'sort_order' => 2,
        ]);

        $this->run->update(['paye_table_id' => $table->id]);

        $service = app(PayrollService::class);
        $run = $service->calculate($this->run->fresh());

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $this->assertGreaterThan(0, (float) $item->paye);
        $this->assertEquals(6000, (float) $item->gross_pay);
        $this->assertEquals(500, (float) $item->paye);
    }

    public function test_calculate_applies_pension_ee(): void
    {
        $scheme = PensionScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Company Pension',
            'employee_rate' => 5,
            'employer_rate' => 10,
            'effective_from' => now()->startOfYear(),
            'is_current' => true,
        ]);

        $this->run->update(['pension_scheme_id' => $scheme->id]);

        $service = app(PayrollService::class);
        $run = $service->calculate($this->run->fresh());

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $this->assertEquals(250, (float) $item->pension_ee);
        $this->assertEquals(500, (float) $item->pension_er);
    }

    public function test_calculate_deducts_active_loan(): void
    {
        EmployeeLoan::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'loan_number' => 'LOAN-001',
            'principal_amount' => 12000,
            'outstanding_balance' => 6000,
            'monthly_deduction' => 500,
            'start_date' => now()->subMonths(6),
            'status' => 'active',
        ]);

        $service = app(PayrollService::class);
        $run = $service->calculate($this->run);

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(6000, (float) $item->gross_pay);
        $this->assertEquals(6000 - 500, (float) $item->net_pay);
    }

    public function test_calculate_net_equals_gross_minus_deductions(): void
    {
        $table = PayeTable::create([
            'company_id' => $this->company->id,
            'version_name' => '2026 Tax',
            'effective_from' => now()->startOfYear(),
            'is_current' => true,
        ]);
        PayeTableBand::create([
            'paye_table_id' => $table->id,
            'threshold' => 0,
            'upper_limit' => null,
            'rate' => 20,
            'sort_order' => 1,
        ]);

        $scheme = PensionScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Pension',
            'employee_rate' => 5,
            'employer_rate' => 10,
            'effective_from' => now()->startOfYear(),
            'is_current' => true,
        ]);

        $this->run->update(['paye_table_id' => $table->id, 'pension_scheme_id' => $scheme->id]);

        $service = app(PayrollService::class);
        $run = $service->calculate($this->run->fresh());

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $expectedNet = round(6000 - $item->paye - $item->pension_ee, 2);
        $this->assertEquals($expectedNet, (float) $item->net_pay);
        $this->assertEquals(round($item->paye + $item->pension_ee, 2), (float) $item->total_deductions);
    }

    public function test_calculate_sets_totals_on_run(): void
    {
        $service = app(PayrollService::class);
        $run = $service->calculate($this->run);

        $item = PayrollRunItem::where('payroll_run_id', $run->id)->first();
        $this->assertEquals($item->gross_pay, $run->fresh()->total_gross);
        $this->assertEquals($item->net_pay, $run->fresh()->total_net_pay);
    }

    public function test_generate_run_number_uses_numbering_service(): void
    {
        \App\Models\NumberingSequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'payroll_run',
            'prefix' => 'PR-',
            'padding_width' => 4,
            'next_number' => 1,
            'reset_policy' => 'monthly',
            'is_active' => true,
        ]);

        $service = app(PayrollService::class);
        $number = $service->generateRunNumber($this->company->id);

        $this->assertNotEmpty($number);
        $this->assertStringStartsWith('PR-', $number);
    }
}
