<?php

namespace App\Services\Payroll;

use App\Models\DefaultAccountMapping;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeePayment;
use App\Models\EmployeeSalaryItem;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\Admin\NumberingSequenceService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    public function __construct(
        private JournalPostingEngine $journalEngine,
        private NumberingSequenceService $numberingService,
    ) {}

    /**
     * Calculate all payroll items for a run.
     *
     * For each active employee:
     *   Gross = Basic + fixed allowances + % allowances + OT (1.5x) + bonus/commission
     *   PAYE  = active tax table applied to taxable gross
     *   Pension EE = employee % x basic
     *   Pension ER = employer % x basic (memo-only)
     *   Loan  = active instalment amount
     *   Net   = Gross - (PAYE + pension_ee + loan + other deductions)
     */
    public function calculate(PayrollRun $run): PayrollRun
    {
        $companyId = (int) $run->company_id;

        $payeTable = $run->payeTable;
        $pensionScheme = $run->pensionScheme;

        $employeeIds = $run->items->pluck('employee_id')->toArray();

        if (empty($employeeIds)) {
            $employeeIds = Employee::forCompany($companyId)
                ->active()
                ->pluck('id')
                ->toArray();
        }

        $employees = Employee::forCompany($companyId)
            ->with(['currentSalaryStructure.items.allowance', 'loans'])
            ->whereIn('id', $employeeIds)
            ->get();

        DB::transaction(function () use ($run, $employees, $payeTable, $pensionScheme, $companyId) {
            $run->items()->delete();

            $totalGross = 0;
            $totalPaye = 0;
            $totalPensionEe = 0;
            $totalPensionEr = 0;
            $totalDeductions = 0;
            $totalNetPay = 0;

            foreach ($employees as $employee) {
                $item = $this->calculateEmployee($employee, $run, $payeTable, $pensionScheme, $companyId);

                $totalGross += $item['gross_pay'];
                $totalPaye += $item['paye'];
                $totalPensionEe += $item['pension_ee'];
                $totalPensionEr += $item['pension_er'];
                $totalDeductions += $item['total_deductions'];
                $totalNetPay += $item['net_pay'];
            }

            $run->update([
                'total_gross'       => $totalGross,
                'total_paye'        => $totalPaye,
                'total_pension_ee'  => $totalPensionEe,
                'total_pension_er'  => $totalPensionEr,
                'total_deductions'  => $totalDeductions,
                'total_net_pay'     => $totalNetPay,
                'status'            => 'calculated',
            ]);
        });

        return $run->fresh('items');
    }

    /**
     * Calculate a single employee's payroll for a run.
     */
    private function calculateEmployee(
        Employee $employee,
        PayrollRun $run,
        ?object $payeTable,
        ?object $pensionScheme,
        int $companyId,
    ): array {
        $structure = $employee->currentSalaryStructure;

        $basicPay = $structure ? (float) $structure->basic_pay : 0;

        $totalAllowances = 0;
        $taxableAllowances = 0;
        $nonTaxableAllowances = 0;
        $allowanceDetails = [];

        if ($structure) {
            foreach ($structure->items as $salaryItem) {
                $amount = (float) $salaryItem->amount;
                $totalAllowances += $amount;
                $isTaxable = $salaryItem->allowance?->is_taxable ?? true;
                if ($isTaxable) {
                    $taxableAllowances += $amount;
                } else {
                    $nonTaxableAllowances += $amount;
                }
                $allowanceDetails[] = [
                    'name'      => $salaryItem->name ?? $salaryItem->allowance?->name ?? 'Allowance',
                    'amount'    => $amount,
                    'is_taxable' => $isTaxable,
                ];
            }
        }

        $grossPay = $basicPay + $totalAllowances;

        $taxableGross = $basicPay + $taxableAllowances;

        $paye = 0;
        if ($payeTable && $taxableGross > 0) {
            $paye = $payeTable->calculatePaye($taxableGross);
        }

        $pensionEe = 0;
        $pensionEr = 0;
        if ($pensionScheme) {
            $pensionEe = $pensionScheme->calculateEmployeeContribution($basicPay);
            $pensionEr = $pensionScheme->calculateEmployerContribution($basicPay);
        }

        $loanDeduction = 0;
        $activeLoans = $employee->loans->where('status', 'active');
        foreach ($activeLoans as $loan) {
            if (!$loan->isFullyPaid()) {
                $loanDeduction += (float) $loan->monthly_deduction;
            }
        }

        $totalDeductions = round($paye + $pensionEe + $loanDeduction, 2);
        $netPay = round($grossPay - $totalDeductions, 2);

        $employerPensionExpense = $pensionEr;

        $payslipData = [
            'employee_name'     => $employee->full_name,
            'employee_number'   => $employee->employee_number,
            'department'        => $employee->department,
            'job_title'         => $employee->job_title,
            'period_start'      => $run->period_start?->format('Y-m-d'),
            'period_end'        => $run->period_end?->format('Y-m-d'),
            'pay_date'          => $run->pay_date?->format('Y-m-d'),
            'basic_pay'         => $basicPay,
            'allowances'        => $allowanceDetails,
            'total_allowances'  => $totalAllowances,
            'gross_pay'         => $grossPay,
            'paye'              => $paye,
            'pension_ee'        => $pensionEe,
            'pension_er'        => $pensionEr,
            'loan_deduction'    => $loanDeduction,
            'total_deductions'  => $totalDeductions,
            'net_pay'           => $netPay,
        ];

        return PayrollRunItem::create([
            'payroll_run_id'            => $run->id,
            'employee_id'               => $employee->id,
            'basic_pay'                 => $basicPay,
            'total_allowances'          => $totalAllowances,
            'gross_pay'                 => $grossPay,
            'paye'                      => $paye,
            'pension_ee'                => $pensionEe,
            'total_deductions'          => $totalDeductions,
            'net_pay'                   => $netPay,
            'pension_er'                => $pensionEr,
            'employer_pension_expense'  => $employerPensionExpense,
            'payslip_data'              => $payslipData,
        ])->toArray();
    }

    /**
     * Post a calculated/approved payroll run to the general ledger.
     *
     * DR Salary Expense (gross + employer pension)
     * CR PAYE Payable
     * CR Pension Payable (EE + ER)
     * CR Loan Receivable
     * CR Net Pay Payable
     */
    public function postToGeneralLedger(PayrollRun $run): \App\Models\JournalEntry
    {
        $companyId = (int) $run->company_id;

        $salaryExpenseId = DefaultAccountMapping::getAccountId($companyId, 'salary_expense');
        $pensionExpenseId = DefaultAccountMapping::getAccountId($companyId, 'pension_expense');
        $payePayableId = DefaultAccountMapping::getAccountId($companyId, 'paye_payable');
        $pensionPayableId = DefaultAccountMapping::getAccountId($companyId, 'pension_payable');
        $loanReceivableId = DefaultAccountMapping::getAccountId($companyId, 'loan_receivable');
        $netPayPayableId = DefaultAccountMapping::getAccountId($companyId, 'net_pay_payable');

        $this->guardMissingAccounts($companyId, [
            'salary_expense'    => $salaryExpenseId,
            'paye_payable'      => $payePayableId,
            'pension_payable'   => $pensionPayableId,
            'net_pay_payable'   => $netPayPayableId,
        ]);

        $totalGross = (float) $run->total_gross;
        $totalPaye = (float) $run->total_paye;
        $totalPensionEe = (float) $run->total_pension_ee;
        $totalPensionEr = (float) $run->total_pension_er;
        $totalLoanDeduction = (float) $run->items->sum(fn ($item) => ($item->payslip_data['loan_deduction'] ?? 0));
        $totalNetPay = (float) $run->total_net_pay;

        $totalSalaryExpense = $totalGross + $totalPensionEr;
        $totalPensionPayable = $totalPensionEe + $totalPensionEr;

        $lines = [];

        $lines[] = [
            'account_id' => $salaryExpenseId,
            'debit'      => round($totalSalaryExpense, 2),
            'credit'     => 0,
            'memo'       => "Payroll: {$run->run_number} - Salary Expense",
        ];

        if ($totalPaye > 0 && $payePayableId) {
            $lines[] = [
                'account_id' => $payePayableId,
                'debit'      => 0,
                'credit'     => round($totalPaye, 2),
                'memo'       => "Payroll: {$run->run_number} - PAYE Payable",
            ];
        }

        if ($totalPensionPayable > 0 && $pensionPayableId) {
            $lines[] = [
                'account_id' => $pensionPayableId,
                'debit'      => 0,
                'credit'     => round($totalPensionPayable, 2),
                'memo'       => "Payroll: {$run->run_number} - Pension Payable (EE+ER)",
            ];
        }

        if ($totalLoanDeduction > 0 && $loanReceivableId) {
            $lines[] = [
                'account_id' => $loanReceivableId,
                'debit'      => 0,
                'credit'     => round($totalLoanDeduction, 2),
                'memo'       => "Payroll: {$run->run_number} - Loan Deductions",
            ];
        }

        if ($totalNetPay > 0 && $netPayPayableId) {
            $lines[] = [
                'account_id' => $netPayPayableId,
                'debit'      => 0,
                'credit'     => round($totalNetPay, 2),
                'memo'       => "Payroll: {$run->run_number} - Net Pay Payable",
            ];
        }

        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \RuntimeException("Journal entry is unbalanced by " . round(abs($totalDebit - $totalCredit), 2));
        }

        $journalEntry = $this->journalEngine->post([
            'company_id'    => $companyId,
            'created_by'    => auth()->id(),
            'date'          => $run->pay_date->format('Y-m-d'),
            'reference'     => $run->run_number,
            'memo'          => "Payroll run {$run->run_number} ({$run->period_start->format('M d')} - {$run->period_end->format('M d, Y')})",
            'source_module' => 'payroll',
            'lines'         => $lines,
        ]);

        $run->update([
            'journal_entry_id' => $journalEntry->id,
            'status'           => 'posted',
            'posted_at'        => now(),
        ]);

        return $journalEntry;
    }

    /**
     * Record salary payments for each employee in a posted payroll run.
     * Each payment creates an EmployeePayment record.
     * Payment JE (DR Net Pay Payable, CR Bank) should be done via the banking handler per batch.
     */
    public function recordPayments(PayrollRun $run): \Illuminate\Support\Collection
    {
        $companyId = (int) $run->company_id;
        $payments = collect();

        DB::transaction(function () use ($run, $companyId, $payments) {
            $items = $run->items()->with('employee')->get();

            foreach ($items as $item) {
                $paymentNumber = $this->numberingService->getNextNumber($companyId, 'employee_payment');

                $payment = EmployeePayment::create([
                    'company_id'     => $companyId,
                    'payroll_run_id' => $run->id,
                    'employee_id'    => $item->employee_id,
                    'payment_number' => $paymentNumber,
                    'payment_date'   => $run->pay_date,
                    'amount'         => $item->net_pay,
                    'payment_type'   => 'salary',
                    'created_by'     => auth()->id(),
                ]);

                $payments->push($payment);
            }

            $totalPaid = $items->sum('net_pay');
            $allPaid = $totalPaid >= (float) $run->total_net_pay;

            $run->update([
                'status'  => $allPaid ? 'fully_paid' : 'partially_paid',
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);
        });

        return $payments;
    }

    /**
     * Generate a payslip data array for a specific employee item.
     */
    public function generatePayslipData(PayrollRunItem $item): array
    {
        return $item->payslip_data ?? [
            'employee_name'    => $item->employee->full_name ?? 'Unknown',
            'employee_number'  => $item->employee->employee_number ?? '',
            'gross_pay'        => $item->gross_pay,
            'paye'             => $item->paye,
            'pension_ee'       => $item->pension_ee,
            'total_deductions' => $item->total_deductions,
            'net_pay'          => $item->net_pay,
        ];
    }

    /**
     * Generate run number for a payroll run.
     */
    public function generateRunNumber(int $companyId): string
    {
        return $this->numberingService->getNextNumber($companyId, 'payroll_run');
    }

    /**
     * Validate that all required GL accounts are mapped.
     */
    private function guardMissingAccounts(int $companyId, array $required): void
    {
        $missing = [];
        foreach ($required as $key => $accountId) {
            if (!$accountId) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                "Missing required GL account mappings for payroll: " . implode(', ', $missing) .
                ". Configure them in Settings > Accounts > Default Account Mappings."
            );
        }
    }
}
