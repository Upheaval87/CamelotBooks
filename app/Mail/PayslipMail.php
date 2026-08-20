<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payslip $payslip,
        public Employee $employee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Payslip — {$this->payslip->payslip_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildBody(),
        );
    }

    protected function buildBody(): string
    {
        $p = $this->payslip;
        $e = $this->employee;
        $company = $p->company;
        $run = $p->payrollRun;
        $empName = $e->full_name ?? 'Employee';
        $companyName = $company->name ?? 'Your Company';
        $period = $run->period_label ?? $run->period_start?->format('M Y') ?? '';
        $payDate = $run->pay_date?->format('d M Y') ?? '';
        $currency = $company->base_currency ?? '';
        $gross = number_format($p->gross_pay, 2);
        $deductions = number_format($p->total_deductions, 2);
        $net = number_format($p->net_pay, 2);

        $earningsRows = '';
        foreach ($p->earnings ?? [] as $e) {
            $amt = number_format($e['amount'] ?? 0, 2);
            $earningsRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-weight:600;'>{$e['item']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;color:#666;'>{$e['basis']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:right;'>{$currency} {$amt}</td>
            </tr>";
        }

        $deductionsRows = '';
        foreach ($p->deductions ?? [] as $d) {
            $amt = number_format($d['amount'] ?? 0, 2);
            $deductionsRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-weight:600;'>{$d['item']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;color:#666;'>{$d['basis']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:right;'>{$currency} {$amt}</td>
            </tr>";
        }

        return <<<HTML
        <div style="font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;color:#111827;">
            <div style="background:linear-gradient(135deg,#17565d,#128F8E);padding:32px;text-align:center;border-radius:16px 16px 0 0;">
                <div style="font-size:24px;font-weight:800;color:#fff;letter-spacing:-0.5px;">{$companyName}</div>
                <div style="color:rgba(255,255,255,0.85);font-size:13px;margin-top:4px;">Confidential — Payslip Delivery</div>
            </div>
            <div style="background:#fff;padding:32px;border:1px solid #e2ecec;border-top:none;border-radius:0 0 16px 16px;">
                <h2 style="font-size:20px;font-weight:800;margin:0 0 8px 0;">Dear {$empName},</h2>
                <p style="color:#41585c;font-size:14px;margin:0 0 24px 0;">Your payslip for <strong>{$period}</strong> is ready. Net pay of <strong>{$currency} {$net}</strong> was processed on <strong>{$payDate}</strong>.</p>

                <table style="width:100%;border-collapse:collapse;margin:0 0 20px 0;">
                    <tr style="background:#f4f8f8;">
                        <th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#5f7476;">Earnings</th>
                        <th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#5f7476;">Basis</th>
                        <th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#5f7476;">Amount</th>
                    </tr>
                    {$earningsRows}
                    <tr style="background:#f4f8f8;">
                        <td colspan="2" style="padding:10px 12px;font-weight:800;">Gross Pay</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:800;">{$currency} {$gross}</td>
                    </tr>
                </table>

                <table style="width:100%;border-collapse:collapse;margin:0 0 20px 0;">
                    <tr style="background:#fef2f2;">
                        <th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#5f7476;">Deductions</th>
                        <th style="padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#5f7476;">Basis</th>
                        <th style="padding:10px 12px;text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#5f7476;">Amount</th>
                    </tr>
                    {$deductionsRows}
                    <tr style="background:#fef2f2;">
                        <td colspan="2" style="padding:10px 12px;font-weight:800;">Total Deductions</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:800;">{$currency} {$deductions}</td>
                    </tr>
                </table>

                <div style="background:linear-gradient(135deg,#17565d,#128F8E);border-radius:12px;padding:20px;text-align:center;margin:24px 0;">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.7);margin-bottom:4px;">Net Pay</div>
                    <div style="font-size:28px;font-weight:800;color:#fff;">{$currency} {$net}</div>
                </div>

                <p style="color:#5f7476;font-size:12px;text-align:center;margin:0;">This is a system-generated email. For questions, contact your payroll administrator.</p>
            </div>
        </div>
        HTML;
    }
}
