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
            $customerRows .= "<tr><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb'>{$name}</td><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb;text-align:right'>" . format_money($c->total) . "</td></tr>";
        }

        $branchRows = '';
        foreach ($d['branch_summary'] as $b) {
            $branchRows .= "<tr><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb'>{$b->branch_name}</td><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb;text-align:right'>" . format_money($b->revenue) . "</td><td style='padding:6px 12px;border-bottom:1px solid #e5e7eb;text-align:right'>" . format_money($b->expenses) . "</td></tr>";
        }

        $html = '<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#333">';
        $html .= '<h2 style="color:#1f2937;margin-bottom:4px">Executive Digest</h2>';
        $html .= '<p style="color:#6b7280;margin-top:0">' . e($this->companyName) . ' &middot; ' . e($d['period_label']) . ' (' . e($d['date_from']) . ' to ' . e($d['date_to']) . ')</p>';

        $html .= '<h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Financial Summary</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse">';
        $html .= '<tr><td style="padding:8px 12px;background:#f9fafb;font-weight:600">Revenue</td><td style="padding:8px 12px;background:#f9fafb;text-align:right">' . format_money($d['revenue']) . '</td></tr>';
        $html .= '<tr><td style="padding:8px 12px;background:#fff;font-weight:600">Expenses</td><td style="padding:8px 12px;background:#fff;text-align:right">' . format_money($d['expenses']) . '</td></tr>';
        $html .= '<tr><td style="padding:8px 12px;background:#f9fafb;font-weight:700;color:' . $netColor . '">Net Income</td><td style="padding:8px 12px;background:#f9fafb;text-align:right;font-weight:700;color:' . $netColor . '">' . format_money($d['net_income']) . '</td></tr>';
        $html .= '</table>';

        $html .= '<h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Cash Position</h3>';
        $html .= '<p style="font-size:20px;font-weight:700;margin:0">' . format_money($d['cash_position']) . '</p>';

        $html .= '<h3 style="margin-top:24px;margin-bottom:8px;color:#374151">A/R Aging</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px">';
        $html .= '<tr><td style="padding:6px 12px;background:#f9fafb">Current</td><td style="padding:6px 12px;background:#f9fafb;text-align:right">' . format_money($ar->current_not_due ?? 0) . '</td></tr>';
        $html .= '<tr><td style="padding:6px 12px;background:#fff">1–30 days</td><td style="padding:6px 12px;background:#fff;text-align:right">' . format_money($ar->days_1_30 ?? 0) . '</td></tr>';
        $html .= '<tr><td style="padding:6px 12px;background:#f9fafb">31–60 days</td><td style="padding:6px 12px;background:#f9fafb;text-align:right">' . format_money($ar->days_31_60 ?? 0) . '</td></tr>';
        $html .= '<tr><td style="padding:6px 12px;background:#fff">61+ days</td><td style="padding:6px 12px;background:#fff;text-align:right">' . format_money($ar->days_61_plus ?? 0) . '</td></tr>';
        $html .= '</table>';

        $html .= '<h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Top Customers</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px">';
        $html .= $customerRows;
        $html .= '</table>';

        $html .= '<h3 style="margin-top:24px;margin-bottom:8px;color:#374151">Branch Summary</h3>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px">';
        $html .= '<tr style="background:#f9fafb"><th style="padding:6px 12px;text-align:left">Branch</th><th style="padding:6px 12px;text-align:right">Revenue</th><th style="padding:6px 12px;text-align:right">Expenses</th></tr>';
        $html .= $branchRows;
        $html .= '</table>';

        $html .= '<p style="margin-top:32px;color:#9ca3af;font-size:12px">This digest was generated automatically. Data sourced from BI data mart.</p>';
        $html .= '</div>';

        return $html;
    }
}
