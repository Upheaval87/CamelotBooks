<?php

namespace App\Services\Accounting;

use App\Models\EmployeeSalaryStructure;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PensionScheme;
use InvalidArgumentException;

class PayrollCalculator
{
    /**
     * Calculate PAYE tax using progressive tax bands.
     * Tax is calculated cumulatively across bands.
     */
    public function calculatePaye(float $taxableIncome, PayeTable $table): float
    {
        if ($taxableIncome <= 0) {
            return 0.0;
        }

        $bands = $table->bands()->orderBy('sort_order', 'asc')->get();

        if ($bands->isEmpty()) {
            return 0.0;
        }

        $tax = 0.0;
        $remainingIncome = $taxableIncome;

        foreach ($bands as $band) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bandWidth = $band->upper_limit
                ? $band->upper_limit - $band->threshold
                : $remainingIncome;

            $taxableInBand = min($remainingIncome, $bandWidth);

            if ($taxableInBand > 0) {
                $tax += $taxableInBand * ($band->rate / 100);
                $remainingIncome -= $taxableInBand;
            }
        }

        return round($tax, 2);
    }

    /**
     * Calculate employee pension contribution.
     */
    public function calculateEmployeePension(float $basicPay, PensionScheme $scheme): float
    {
        $contributorySalary = $basicPay;

        if ($scheme->max_contributory_salary && $basicPay > $scheme->max_contributory_salary) {
            $contributorySalary = $scheme->max_contributory_salary;
        }

        return round($contributorySalary * ($scheme->employee_rate / 100), 2);
    }

    /**
     * Calculate employer pension contribution.
     */
    public function calculateEmployerPension(float $basicPay, PensionScheme $scheme): float
    {
        $contributorySalary = $basicPay;

        if ($scheme->max_contributory_salary && $basicPay > $scheme->max_contributory_salary) {
            $contributorySalary = $scheme->max_contributory_salary;
        }

        return round($contributorySalary * ($scheme->employer_rate / 100), 2);
    }

    /**
     * Calculate all payroll amounts for a single employee.
     *
     * @return array{basic_pay: float, allowances: float, gross_pay: float, paye: float, pension_ee: float, pension_er: float, total_other_deductions: float, total_deductions: float, net_pay: float, employer_pension_expense: float}
     */
    public function calculateEmployeePayroll(EmployeeSalaryStructure $salaryStructure, PayeTable $payeTable, ?PensionScheme $pensionScheme, float $totalOtherDeductions = 0): array
    {
        $basicPay = (float) $salaryStructure->basic_pay;

        $allowances = $salaryStructure->items()
            ->where('type', 'allowance')
            ->sum('amount');

        $grossPay = $basicPay + (float) $allowances;

        $taxableIncome = $grossPay;

        $paye = $this->calculatePaye($taxableIncome, $payeTable);

        $pensionEe = 0.0;
        $pensionEr = 0.0;
        $employerPensionExpense = 0.0;

        if ($pensionScheme) {
            $pensionEe = $this->calculateEmployeePension($basicPay, $pensionScheme);
            $pensionEr = $this->calculateEmployerPension($basicPay, $pensionScheme);
            $employerPensionExpense = $pensionEr;
        }

        $totalDeductions = $paye + $pensionEe + $totalOtherDeductions;
        $netPay = $grossPay - $totalDeductions;

        return [
            'basic_pay' => round($basicPay, 2),
            'allowances' => round($allowances, 2),
            'gross_pay' => round($grossPay, 2),
            'paye' => round($paye, 2),
            'pension_ee' => round($pensionEe, 2),
            'pension_er' => round($pensionEr, 2),
            'total_other_deductions' => round($totalOtherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'employer_pension_expense' => round($employerPensionExpense, 2),
        ];
    }

    /**
     * Calculate totals for a set of employee payroll results.
     */
    public function calculateRunTotals(array $employeeResults): array
    {
        $totals = [
            'total_gross' => 0.0,
            'total_paye' => 0.0,
            'total_pension_ee' => 0.0,
            'total_pension_er' => 0.0,
            'total_deductions' => 0.0,
            'total_net_pay' => 0.0,
        ];

        foreach ($employeeResults as $result) {
            $totals['total_gross'] += $result['gross_pay'];
            $totals['total_paye'] += $result['paye'];
            $totals['total_pension_ee'] += $result['pension_ee'];
            $totals['total_pension_er'] += $result['pension_er'];
            $totals['total_deductions'] += $result['total_deductions'];
            $totals['total_net_pay'] += $result['net_pay'];
        }

        return array_map(fn($v) => round($v, 2), $totals);
    }
}
