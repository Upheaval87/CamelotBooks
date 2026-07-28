<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\DefaultAccountMapping;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\EmployeeSalaryStructure;
use App\Models\EmployeeSalaryItem;
use App\Models\PayeTable;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PensionScheme;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    protected JournalPostingEngine $postingEngine;
    protected PayrollCalculator $calculator;
    protected NumberingSequenceService $numberingService;

    public function __construct(
        JournalPostingEngine $postingEngine,
        PayrollCalculator $calculator,
        NumberingSequenceService $numberingService
    ) {
        $this->postingEngine = $postingEngine;
        $this->calculator = $calculator;
        $this->numberingService = $numberingService;
    }

    public function runPayroll(int $companyId, string $periodLabel, string $payDate, string $periodStart, string $periodEnd, int $userId): PayrollRun
    {
        $payeTable = PayeTable::where('company_id', $companyId)
            ->current()
            ->where('effective_from', '<=', $payDate)
            ->first();

        if (!$payeTable) {
            throw new InvalidArgumentException("No PAYE table found effective for {$payDate}. Please configure a PAYE table first.");
        }

        $pensionScheme = PensionScheme::where('company_id', $companyId)
            ->current()
            ->where('effective_from', '<=', $payDate)
            ->first();

        return DB::transaction(function () use ($companyId, $periodLabel, $payDate, $periodStart, $periodEnd, $userId, $payeTable, $pensionScheme) {
            $runNumber = $this->numberingService->getNextNumber($companyId, 'payroll_run');

            $run = PayrollRun::create([
                'company_id' => $companyId,
                'run_number' => $runNumber,
                'period_label' => $periodLabel,
                'pay_date' => $payDate,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => PayrollRun::STATUS_CALCULATED,
                'paye_table_id' => $payeTable->id,
                'pension_scheme_id' => $pensionScheme?->id,
                'created_by' => $userId,
            ]);

            $employees = Employee::where('company_id', $companyId)->active()->get();

            $grossTotal = 0;
            $payeTotal = 0;
            $pensionEeTotal = 0;
            $pensionErTotal = 0;
            $deductionsTotal = 0;
            $netTotal = 0;

            foreach ($employees as $employee) {
                $salaryStructure = EmployeeSalaryStructure::where('employee_id', $employee->id)
                    ->current()
                    ->first();

                if (!$salaryStructure || $salaryStructure->basic_pay <= 0) {
                    continue;
                }

                $otherDeductions = (float) $salaryStructure->items()
                    ->where('type', 'deduction')
                    ->sum('amount');

                $result = $this->calculator->calculateEmployeePayroll(
                    $salaryStructure,
                    $payeTable,
                    $pensionScheme,
                    $otherDeductions
                );

                PayrollRunItem::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'basic_pay' => $result['basic_pay'],
                    'total_allowances' => $result['allowances'],
                    'gross_pay' => $result['gross_pay'],
                    'paye' => $result['paye'],
                    'pension_ee' => $result['pension_ee'],
                    'total_deductions' => $result['total_deductions'],
                    'net_pay' => $result['net_pay'],
                    'pension_er' => $result['pension_er'],
                    'employer_pension_expense' => $result['employer_pension_expense'],
                    'payslip_data' => $this->buildPayslipData($employee, $result, $periodLabel),
                ]);

                $grossTotal += $result['gross_pay'];
                $payeTotal += $result['paye'];
                $pensionEeTotal += $result['pension_ee'];
                $pensionErTotal += $result['pension_er'];
                $deductionsTotal += $result['total_deductions'];
                $netTotal += $result['net_pay'];
            }

            $run->update([
                'total_gross' => round($grossTotal, 2),
                'total_paye' => round($payeTotal, 2),
                'total_pension_ee' => round($pensionEeTotal, 2),
                'total_pension_er' => round($pensionErTotal, 2),
                'total_deductions' => round($deductionsTotal, 2),
                'total_net_pay' => round($netTotal, 2),
            ]);

            return $run->fresh();
        });
    }

    public function approvePayroll(PayrollRun $run, int $userId): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_CALCULATED) {
            throw new InvalidArgumentException('Only calculated payroll runs can be approved.');
        }

        $run->update([
            'status' => PayrollRun::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $userId,
        ]);

        return $run->fresh();
    }

    public function postPayroll(PayrollRun $run, int $userId): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_APPROVED) {
            throw new InvalidArgumentException('Only approved payroll runs can be posted.');
        }

        $companyId = $run->company_id;

        $salaryExpenseAccount = DefaultAccountMapping::getAccount($companyId, 'salary_expense');
        $pensionExpenseAccount = DefaultAccountMapping::getAccount($companyId, 'pension_expense');
        $payePayableAccount = DefaultAccountMapping::getAccount($companyId, 'paye_payable');
        $pensionPayableAccount = DefaultAccountMapping::getAccount($companyId, 'pension_payable');
        $netPayPayableAccount = DefaultAccountMapping::getAccount($companyId, 'net_pay_payable');

        if (!$salaryExpenseAccount || !$payePayableAccount || !$pensionPayableAccount || !$netPayPayableAccount) {
            throw new InvalidArgumentException('Required payroll GL accounts not found. Please check Default Account Mappings in System Settings.');
        }

        $items = $run->items()->with('employee')->get();

        $jeLines = [];

        $jeLines[] = [
            'account_id' => $salaryExpenseAccount->id,
            'debit' => $run->total_gross,
            'credit' => 0,
            'memo' => "Payroll {$run->run_number} - Salaries & Wages (Gross)",
        ];

        if ($pensionExpenseAccount && $run->total_pension_er > 0) {
            $jeLines[] = [
                'account_id' => $pensionExpenseAccount->id,
                'debit' => $run->total_pension_er,
                'credit' => 0,
                'memo' => "Payroll {$run->run_number} - Employer Pension",
            ];
        }

        $jeLines[] = [
            'account_id' => $payePayableAccount->id,
            'debit' => 0,
            'credit' => $run->total_paye,
            'memo' => "Payroll {$run->run_number} - PAYE Payable",
        ];

        $totalPensionPayable = $run->total_pension_ee + $run->total_pension_er;

        if ($totalPensionPayable > 0) {
            $jeLines[] = [
                'account_id' => $pensionPayableAccount->id,
                'debit' => 0,
                'credit' => $totalPensionPayable,
                'memo' => "Payroll {$run->run_number} - Pension Payable",
            ];
        }

        $jeLines[] = [
            'account_id' => $netPayPayableAccount->id,
            'debit' => 0,
            'credit' => $run->total_net_pay,
            'memo' => "Payroll {$run->run_number} - Net Pay Payable",
        ];

        $journalEntry = $this->postingEngine->post([
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $run->pay_date->format('Y-m-d'),
            'source_module' => 'payroll',
            'reference' => $run->run_number,
            'memo' => "Payroll run {$run->run_number} - {$run->period_label}",
            'lines' => $jeLines,
        ]);

        $run->update([
            'status' => PayrollRun::STATUS_POSTED,
            'journal_entry_id' => $journalEntry->id,
        ]);

        return $run->fresh();
    }

    public function payEmployee(PayrollRun $run, int $employeeId, float $amount, string $paymentDate, int $bankAccountId, int $userId): EmployeePayment
    {
        if (!in_array($run->status, [PayrollRun::STATUS_POSTED, PayrollRun::STATUS_PARTIALLY_PAID])) {
            throw new InvalidArgumentException('Payroll run must be posted before paying employees.');
        }

        $companyId = $run->company_id;

        $netPayPayableAccount = DefaultAccountMapping::getAccount($companyId, 'net_pay_payable');
        $bankAccount = Account::find($bankAccountId);

        if (!$netPayPayableAccount || !$bankAccount) {
            throw new InvalidArgumentException('Net Pay Payable account or bank account not found.');
        }

        return DB::transaction(function () use ($run, $employeeId, $amount, $paymentDate, $bankAccountId, $userId, $companyId, $netPayPayableAccount, $bankAccount) {
            $paymentNumber = $this->numberingService->getNextNumber($companyId, 'employee_payment');

            $jeLines = [
                [
                    'account_id' => $netPayPayableAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Salary payment {$paymentNumber}",
                ],
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Salary payment {$paymentNumber}",
                ],
            ];

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $paymentDate,
                'source_module' => 'employee_payment',
                'reference' => $paymentNumber,
                'memo' => "Salary payment - Employee {$employeeId}",
                'lines' => $jeLines,
            ]);

            $payment = EmployeePayment::create([
                'company_id' => $companyId,
                'payroll_run_id' => $run->id,
                'employee_id' => $employeeId,
                'payment_number' => $paymentNumber,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_type' => 'salary',
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'created_by' => $userId,
            ]);

            $this->updateRunPaymentStatus($run);

            return $payment;
        });
    }

    public function remitPAYE(PayrollRun $run, float $amount, string $paymentDate, int $bankAccountId, int $userId): EmployeePayment
    {
        if ($run->status !== PayrollRun::STATUS_POSTED && $run->status !== PayrollRun::STATUS_PARTIALLY_PAID) {
            throw new InvalidArgumentException('Payroll run must be posted before remitting PAYE.');
        }

        $companyId = $run->company_id;

        $payePayableAccount = DefaultAccountMapping::getAccount($companyId, 'paye_payable');
        $bankAccount = Account::find($bankAccountId);

        if (!$payePayableAccount || !$bankAccount) {
            throw new InvalidArgumentException('PAYE Payable account or bank account not found.');
        }

        return DB::transaction(function () use ($run, $amount, $paymentDate, $bankAccountId, $userId, $companyId, $payePayableAccount, $bankAccount) {
            $paymentNumber = $this->numberingService->getNextNumber($companyId, 'employee_payment');

            $jeLines = [
                [
                    'account_id' => $payePayableAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "PAYE remittance {$paymentNumber}",
                ],
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "PAYE remittance {$paymentNumber}",
                ],
            ];

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $paymentDate,
                'source_module' => 'employee_payment',
                'reference' => $paymentNumber,
                'memo' => "PAYE remittance - {$run->run_number}",
                'lines' => $jeLines,
            ]);

            return EmployeePayment::create([
                'company_id' => $companyId,
                'payroll_run_id' => $run->id,
                'employee_id' => null,
                'payment_number' => $paymentNumber,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_type' => 'paye_remittance',
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'created_by' => $userId,
            ]);
        });
    }

    public function remitPension(PayrollRun $run, float $amount, string $paymentDate, int $bankAccountId, int $userId): EmployeePayment
    {
        if ($run->status !== PayrollRun::STATUS_POSTED && $run->status !== PayrollRun::STATUS_PARTIALLY_PAID) {
            throw new InvalidArgumentException('Payroll run must be posted before remitting pension.');
        }

        $companyId = $run->company_id;

        $pensionPayableAccount = DefaultAccountMapping::getAccount($companyId, 'pension_payable');
        $bankAccount = Account::find($bankAccountId);

        if (!$pensionPayableAccount || !$bankAccount) {
            throw new InvalidArgumentException('Pension Payable account or bank account not found.');
        }

        return DB::transaction(function () use ($run, $amount, $paymentDate, $bankAccountId, $userId, $companyId, $pensionPayableAccount, $bankAccount) {
            $paymentNumber = $this->numberingService->getNextNumber($companyId, 'employee_payment');

            $jeLines = [
                [
                    'account_id' => $pensionPayableAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Pension remittance {$paymentNumber}",
                ],
                [
                    'account_id' => $bankAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Pension remittance {$paymentNumber}",
                ],
            ];

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $paymentDate,
                'source_module' => 'employee_payment',
                'reference' => $paymentNumber,
                'memo' => "Pension remittance - {$run->run_number}",
                'lines' => $jeLines,
            ]);

            return EmployeePayment::create([
                'company_id' => $companyId,
                'payroll_run_id' => $run->id,
                'employee_id' => null,
                'payment_number' => $paymentNumber,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_type' => 'pension_remittance',
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => $journalEntry->id,
                'created_by' => $userId,
            ]);
        });
    }

    protected function updateRunPaymentStatus(PayrollRun $run): void
    {
        $totalPaid = EmployeePayment::where('payroll_run_id', $run->id)
            ->where('payment_type', 'salary')
            ->sum('amount');

        if ($totalPaid >= $run->total_net_pay) {
            $run->update(['status' => PayrollRun::STATUS_FULLY_PAID]);
        } elseif ($totalPaid > 0) {
            $run->update(['status' => PayrollRun::STATUS_PARTIALLY_PAID]);
        }
    }

    protected function buildPayslipData(Employee $employee, array $result, string $periodLabel): array
    {
        return [
            'employee_name' => $employee->full_name,
            'employee_number' => $employee->employee_number,
            'period' => $periodLabel,
            'position' => $employee->position,
            'department' => $employee->department,
            'tax_id' => $employee->tax_id,
            'pension_member_number' => $employee->pension_member_number,
            'basic_pay' => $result['basic_pay'],
            'allowances' => $result['allowances'],
            'gross_pay' => $result['gross_pay'],
            'paye' => $result['paye'],
            'pension_ee' => $result['pension_ee'],
            'other_deductions' => $result['total_other_deductions'],
            'total_deductions' => $result['total_deductions'],
            'net_pay' => $result['net_pay'],
        ];
    }
}
