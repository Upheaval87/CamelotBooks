<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\PayrollRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PayrollRun $run,
        public Employee $employee,
        public ?string $pdfContent = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Payslip - {$this->run->period_label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildBody(),
        );
    }

    public function attachments(): array
    {
        if (!$this->pdfContent) {
            return [];
        }

        return [
            Attachment::fromRaw($this->pdfContent)
                ->as("payslip_{$this->run->run_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    protected function buildBody(): string
    {
        $employeeName = $this->employee->full_name;
        $period = $this->run->period_label;
        $companyName = $this->run->company->name ?? 'Your Company';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Payslip Available</h2>
            <p>Dear {$employeeName},</p>
            <p>Please find attached your payslip for <strong>{$period}</strong>.</p>
            <p>The PDF is password-protected. Your password was provided to you separately.</p>
            <p>If you have any questions, please contact the payroll department.</p>
            <br/>
            <p>Regards,<br/>{$companyName}</p>
        </div>
        HTML;
    }
}
