<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class EncryptedPayslipService
{
    private string $ownerPassword;

    public function __construct()
    {
        $this->ownerPassword = config('app.key', 'system-owner-key');
    }

    public function generatePayslipPdf(PayrollRun $run, PayrollRunItem $item): string
    {
        $employee = $item->employee;
        $payslipData = $item->payslip_data ?? [];
        $company = $run->company;

        $userPassword = $this->getEmployeePassword($employee);

        $html = $this->buildPayslipHtml($run, $employee, $payslipData, $company);

        $mpdf = $this->createMpdf($userPassword);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generatePayslipPdfToDisk(PayrollRun $run, PayrollRunItem $item, ?string $directory = null): string
    {
        $content = $this->generatePayslipPdf($run, $item);

        $directory = $directory ?? "payslips/{$run->run_number}";
        $filename = "payslip_{$item->employee->employee_number}.pdf";

        $path = storage_path("app/{$directory}");
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents("{$path}/{$filename}", $content);

        return "{$directory}/{$filename}";
    }

    public function getEmployeePassword(Employee $employee): string
    {
        $decrypted = $employee->payslip_password_decrypted;

        if ($decrypted) {
            return $decrypted;
        }

        return $this->generateDefaultPassword($employee);
    }

    public function generateDefaultPassword(Employee $employee): string
    {
        $rule = config('payroll.payslip_password.default_generation_rule', 'tax_id_last4+birth_year');

        $taxId = $employee->tax_id ?? '';
        $taxIdLast4 = strlen($taxId) >= 4 ? substr($taxId, -4) : $taxId;
        $birthYear = $employee->date_of_birth ? $employee->date_of_birth->format('Y') : '';
        $lastName = strtolower($employee->last_name ?? '');

        return match ($rule) {
            'tax_id_last4+birth_year' => $taxIdLast4 . $birthYear,
            'tax_id_last4' => $taxIdLast4,
            'birth_year+last_name' => $birthYear . $lastName,
            'employee_number' => $employee->employee_number,
            default => $taxIdLast4 . $birthYear,
        };
    }

    protected function createMpdf(string $userPassword): Mpdf
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => sys_get_temp_dir() . '/mpdf',
            'fontDir' => [storage_path('fonts')],
            'fontdata' => [
                'inter' => [
                    'R' => 'inter/Inter-Regular.ttf',
                    'I' => 'inter/Inter-Italic.ttf',
                    'B' => 'inter/Inter-Bold.ttf',
                    'BI' => 'inter/Inter-BoldItalic.ttf',
                    'M' => 'inter/Inter-Medium.ttf',
                    'SB' => 'inter/Inter-SemiBold.ttf',
                    'XB' => 'inter/Inter-ExtraBold.ttf',
                    'BL' => 'inter/Inter-Black.ttf',
                ],
            ],
            'default_font' => 'inter',
        ]);

        $mpdf->SetProtection(
            ['print', 'copy'],
            $userPassword,
            $this->ownerPassword,
            128
        );

        return $mpdf;
    }

    protected function buildPayslipHtml(PayrollRun $run, Employee $employee, array $data, $company): string
    {
        $period = $run->period_label;
        $companyName = $company->name ?? 'Company';
        $employeeName = $data['employee_name'] ?? $employee->full_name;
        $employeeNumber = $data['employee_number'] ?? $employee->employee_number;
        $position = $data['position'] ?? $employee->position ?? '';
        $department = $data['department'] ?? $employee->department ?? '';
        $taxId = $data['tax_id'] ?? $employee->tax_id ?? '';
        $currency = $company->base_currency ?? 'MWK';

        $basicPay = number_format($data['basic_pay'] ?? 0, 2);
        $allowances = number_format($data['allowances'] ?? 0, 2);
        $grossPay = number_format($data['gross_pay'] ?? 0, 2);
        $paye = number_format($data['paye'] ?? 0, 2);
        $pensionEe = number_format($data['pension_ee'] ?? 0, 2);
        $otherDeductions = number_format($data['other_deductions'] ?? 0, 2);
        $totalDeductions = number_format($data['total_deductions'] ?? 0, 2);
        $netPay = number_format($data['net_pay'] ?? 0, 2);

        return <<<HTML
        <style>
            body { font-family: 'Inter', sans-serif; }
            .payslip-header { text-align: center; margin-bottom: 10px; }
            .payslip-header h2 { font-size: 16pt; margin: 2px 0; }
            .payslip-header h3 { font-size: 12pt; color: #555; margin: 2px 0; }
            .employee-info { margin-bottom: 15px; font-size: 9pt; }
            .employee-info table { width: 100%; }
            .employee-info td { padding: 2px 5px; }
            .employee-info .label { font-weight: bold; width: 120px; }
            .earnings-deductions { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .earnings-deductions th { background-color: #333; color: #fff; padding: 6px 10px; text-align: left; font-size: 9pt; }
            .earnings-deductions td { padding: 5px 10px; border-bottom: 1px solid #ddd; font-size: 9pt; }
            .earnings-deductions .amount { text-align: right; width: 120px; }
            .earnings-deductions .total-row td { font-weight: bold; border-top: 2px solid #333; background-color: #f5f5f5; }
            .net-pay { text-align: center; margin: 15px 0; padding: 10px; background-color: #e8f5e9; border: 1px solid #4caf50; }
            .net-pay .amount { font-size: 14pt; font-weight: bold; color: #2e7d32; }
            .footer-note { text-align: center; font-size: 7pt; color: #999; margin-top: 20px; }
        </style>

        <div class="payslip-header">
            <h2>{$companyName}</h2>
            <h3>Payslip — {$period}</h3>
        </div>

        <div class="employee-info">
            <table>
                <tr>
                    <td class="label">Employee:</td><td>{$employeeName}</td>
                    <td class="label">Employee No:</td><td>{$employeeNumber}</td>
                </tr>
                <tr>
                    <td class="label">Position:</td><td>{$position}</td>
                    <td class="label">Department:</td><td>{$department}</td>
                </tr>
                <tr>
                    <td class="label">Tax ID:</td><td>{$taxId}</td>
                    <td class="label">Pay Date:</td><td>{$run->pay_date->format('d M Y')}</td>
                </tr>
            </table>
        </div>

        <table class="earnings-deductions">
            <tr>
                <th colspan="2">Earnings</th>
                <th class="amount">Amount ({$currency})</th>
            </tr>
            <tr>
                <td colspan="2">Basic Pay</td>
                <td class="amount">{$basicPay}</td>
            </tr>
            <tr>
                <td colspan="2">Allowances</td>
                <td class="amount">{$allowances}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2">Gross Pay</td>
                <td class="amount">{$grossPay}</td>
            </tr>
        </table>

        <table class="earnings-deductions">
            <tr>
                <th colspan="2">Deductions</th>
                <th class="amount">Amount</th>
            </tr>
            <tr>
                <td colspan="2">PAYE</td>
                <td class="amount">{$paye}</td>
            </tr>
            <tr>
                <td colspan="2">Pension (Employee)</td>
                <td class="amount">{$pensionEe}</td>
            </tr>
            <tr>
                <td colspan="2">Other Deductions</td>
                <td class="amount">{$otherDeductions}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2">Total Deductions</td>
                <td class="amount">{$totalDeductions}</td>
            </tr>
        </table>

        <div class="net-pay">
            <div>NET PAY</div>
            <div class="amount">{$netPay}</div>
        </div>

        <div class="footer-note">
            This is a computer-generated payslip. Please retain for your records.<br/>
            Payroll Reference: {$run->run_number}
        </div>
        HTML;
    }
}
