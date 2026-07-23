<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\EmployeeSalaryItem;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PensionScheme;
use App\Models\User;
use App\Services\Accounting\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected PayrollCalculator $calculator;

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

        $this->calculator = new PayrollCalculator();
    }

    protected function createPayeTable(array $bands = null): PayeTable
    {
        $table = PayeTable::create([
            'company_id' => $this->company->id,
            'version_name' => 'Test PAYE Table',
            'effective_from' => now()->subMonth(),
            'is_current' => true,
        ]);

        if ($bands === null) {
            $bands = [
                ['threshold' => 0, 'upper_limit' => 100000, 'rate' => 0],
                ['threshold' => 100000, 'upper_limit' => 200000, 'rate' => 15],
                ['threshold' => 200000, 'upper_limit' => 500000, 'rate' => 25],
                ['threshold' => 500000, 'upper_limit' => null, 'rate' => 30],
            ];
        }

        foreach ($bands as $index => $band) {
            PayeTableBand::create([
                'paye_table_id' => $table->id,
                'threshold' => $band['threshold'],
                'upper_limit' => $band['upper_limit'],
                'rate' => $band['rate'],
                'sort_order' => $index,
            ]);
        }

        return $table;
    }

    protected function createPensionScheme(float $employeeRate = 5.0, float $employerRate = 10.0, ?float $maxSalary = null): PensionScheme
    {
        return PensionScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Test Pension',
            'employee_rate' => $employeeRate,
            'employer_rate' => $employerRate,
            'max_contributory_salary' => $maxSalary,
            'effective_from' => now()->subMonth(),
            'is_current' => true,
        ]);
    }

    protected function createEmployeeWithSalary(float $basicPay, string $employeeNumber = 'EMP-001'): EmployeeSalaryStructure
    {
        $employee = Employee::create([
            'company_id' => $this->company->id,
            'employee_number' => $employeeNumber,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'hire_date' => now()->subYear(),
            'employment_status' => 'active',
            'is_active' => true,
        ]);

        return EmployeeSalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_pay' => $basicPay,
            'effective_from' => now()->subYear(),
            'is_current' => true,
        ]);
    }

    // ── PAYE Tests ──────────────────────────────────────────

    public function test_paye_zero_on_income_below_first_threshold(): void
    {
        $table = $this->createPayeTable();
        $result = $this->calculator->calculatePaye(0, $table);
        $this->assertEquals(0, $result);
    }

    public function test_paye_zero_in_tax_free_band(): void
    {
        $table = $this->createPayeTable();
        $result = $this->calculator->calculatePaye(80000, $table);
        $this->assertEquals(0, $result);
    }

    public function test_paye_calculated_progressively(): void
    {
        $table = $this->createPayeTable();

        // 300,000: 0-100k=0%, 100k-200k=15%=15k, 200k-300k=25%=25k → total 40k
        $result = $this->calculator->calculatePaye(300000, $table);
        $this->assertEquals(40000, $result);
    }

    public function test_paye_top_band_only_taxes_amount_above_threshold(): void
    {
        $table = $this->createPayeTable();

        // 700,000: 0-100k=0, 100k-200k=15k, 200k-500k=75k, 500k-700k=60k → total 150k
        $result = $this->calculator->calculatePaye(700000, $table);
        $this->assertEquals(150000, $result);
    }

    public function test_paye_negative_income_returns_zero(): void
    {
        $table = $this->createPayeTable();
        $result = $this->calculator->calculatePaye(-1000, $table);
        $this->assertEquals(0, $result);
    }

    // ── Pension Tests ───────────────────────────────────────

    public function test_employee_pension_deducted_at_normal_rate(): void
    {
        $scheme = $this->createPensionScheme(5.0, 10.0);
        $result = $this->calculator->calculateEmployeePension(200000, $scheme);
        $this->assertEquals(10000, $result);
    }

    public function test_employer_pension_calculated(): void
    {
        $scheme = $this->createPensionScheme(5.0, 10.0);
        $result = $this->calculator->calculateEmployerPension(200000, $scheme);
        $this->assertEquals(20000, $result);
    }

    public function test_pension_respects_ceiling(): void
    {
        $scheme = $this->createPensionScheme(5.0, 10.0, 300000);
        // Only 300k contributory
        $result = $this->calculator->calculateEmployeePension(500000, $scheme);
        $this->assertEquals(15000, $result);
    }

    public function test_pension_no_ceiling_when_null(): void
    {
        $scheme = $this->createPensionScheme(5.0, 10.0, null);
        $result = $this->calculator->calculateEmployeePension(500000, $scheme);
        $this->assertEquals(25000, $result);
    }

    // ── Full Employee Calculation ──────────────────────────

    public function test_calculate_employee_payroll_full_scenario(): void
    {
        $table = $this->createPayeTable();
        $scheme = $this->createPensionScheme(5.0, 10.0);
        $salaryStructure = $this->createEmployeeWithSalary(250000);

        EmployeeSalaryItem::create([
            'salary_structure_id' => $salaryStructure->id,
            'company_allowance_id' => null,
            'name' => 'Housing Allowance',
            'type' => 'allowance',
            'amount' => 50000,
        ]);
        EmployeeSalaryItem::create([
            'salary_structure_id' => $salaryStructure->id,
            'company_allowance_id' => null,
            'name' => 'Transport Allowance',
            'type' => 'allowance',
            'amount' => 20000,
        ]);

        $result = $this->calculator->calculateEmployeePayroll($salaryStructure, $table, $scheme, 2000);

        // Gross: 250k + 50k + 20k = 320,000
        $this->assertEquals(320000, $result['gross_pay']);

        // Pension on basicPay: 250k * 5% = 12,500
        $this->assertEquals(12500, $result['pension_ee']);
        $this->assertEquals(25000, $result['pension_er']);

        // PAYE on 320k: 0 + 15k + 30k = 45,000
        $this->assertEquals(45000, $result['paye']);

        // Total deductions: 45k + 12.5k + 2k = 59,500
        $this->assertEquals(59500, $result['total_deductions']);

        // Net: 320k - 59.5k = 260,500
        $this->assertEquals(260500, $result['net_pay']);
    }

    public function test_calculate_run_totals(): void
    {
        $table = $this->createPayeTable();
        $scheme = $this->createPensionScheme(5.0, 10.0);

        $employee1 = $this->createEmployeeWithSalary(250000, 'EMP-001');
        EmployeeSalaryItem::create([
            'salary_structure_id' => $employee1->id,
            'company_allowance_id' => null,
            'name' => 'Housing',
            'type' => 'allowance',
            'amount' => 50000,
        ]);

        $employee2 = $this->createEmployeeWithSalary(150000, 'EMP-002');

        $result1 = $this->calculator->calculateEmployeePayroll($employee1, $table, $scheme);
        $result2 = $this->calculator->calculateEmployeePayroll($employee2, $table, $scheme);

        $totals = $this->calculator->calculateRunTotals([$result1, $result2]);

        $this->assertArrayHasKey('total_gross', $totals);
        $this->assertArrayHasKey('total_paye', $totals);
        $this->assertArrayHasKey('total_pension_ee', $totals);
        $this->assertArrayHasKey('total_pension_er', $totals);
        $this->assertArrayHasKey('total_deductions', $totals);
        $this->assertArrayHasKey('total_net_pay', $totals);

        $this->assertEquals(
            $result1['gross_pay'] + $result2['gross_pay'],
            $totals['total_gross']
        );
    }
}
