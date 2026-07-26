<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExecutiveDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $digest,
        public string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Executive Digest — {$this->digest['period_label']}",
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
        $d = $this->digest;
        $netColor = $d['net_income'] >= 0 ? '#059669' : '#dc2626';
        $ar = $d['ar_aging'];

        $customerRows = '';
        foreach ($d['top_customers'] as $c) {
            $name = $c->customer_name ?? 'Walk-in';
            $customerRows .= "<tr><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb'>{$name}</td><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb;text-align:right'>\$" . number_format($c->total, 2) . "</td></tr>";
        }

        $branchRows = '';
        foreach ($d['branch_summary'] as $b) {
            $branchRows .= "<tr><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb'>{$b->branch_name}</td><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb;text-align:right'>\$" . number_format($b->revenue, 2) . "</td><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb;text-align:right'>\$" . number_format($b->expenses, 2) . "</td></tr>";
        }

        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#333">
            <h2 style="color:#1f2937;margin-bottom:4px">Executive Digest</h2>
            <p style="color:#6b7280;margin-top:0">{$this->companyName} &middot; {$d['period_label']} ({$d['date_from']} to {$d['date_to']})</p>

            <h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Financial Summary</h3>
            <table style="width:100%;border-collapse:collapse">
                <tr><td style="padding:8px 12px;background:#f9fafb;font-weight:600">Revenue</td><td style="padding:8px 12px;background:#f9fafb;text-align:right">\$" . number_format($d['revenue'], 2) . "</td></tr>
                <tr><td style="padding:8px 12px;background:#fff;font-weight:600">Expenses</td><td style="padding:8px 12px;background:#fff;text-align:right">\$" . number_format($d['expenses'], 2) . "</td></tr>
                <tr><td style="padding:8px 12px;background:#f9fafb;font-weight:700;color:{$netColor}">Net Income</td><td style="padding:8px 12px;background:#f9fafb;text-align:right;font-weight:700;color:{$netColor}">\$" . number_format($d['net_income'], 2) . "</td></tr>
            </table>

            <h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Cash Position</h3>
            <p style="font-size:20px;font-weight:700;margin:0">\$" . number_format($d['cash_position'], 2) . "</p>

            <h3 style="margin-top:24px;margin-bottom:8px;color:#374151">A/R Aging</h3>
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tr><td style="padding:6px 12px;background:#f9fafb">Current</td><td style="padding:6px 12px;background:#f9fafb;text-align:right">\$" . number_format($ar->current_not_due ?? 0, 2) . "</td></tr>
                <tr><td style="padding:6px 12px;background:#fff">1–30 days</td><td style="padding:6px 12px;background:#fff;text-align:right">\$" . number_format($ar->days_1_30 ?? 0, 2) . "</td></tr>
                <tr><td style="padding:6px 12px;background:#f9fafb">31–60 days</td><td style="padding:6px 12px;background:#f9fafb;text-align:right">\$" . number_format($ar->days_31_60 ?? 0, 2) . "</td></tr>
                <tr><td style="padding:6px 12px;background:#fff">61+ days</td><td style="padding:6px 12px;background:#fff;text-align:right">\$" . number_format($ar->days_61_plus ?? 0, 2) . "</td></tr>
            </table>

            <h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Top Customers</h3>
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                {$customerRows}
            </table>

            <h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Branch Summary</h3>
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tr style="background:#f9fafb"><th style='padding:6px 12px;text-align:left'>Branch</th><th style='padding:6px 12px;text-align:right'>Revenue</th><th style='padding:6px 12px;text-align:right'>Expenses</th></tr>
                {$branchRows}
            </table>

            <p style="margin-top:32px;color:#9ca3af;font-size:12px">This digest was generated automatically. Data sourced from BI data mart.</p>
        </div>
        HTML;
    }
}
