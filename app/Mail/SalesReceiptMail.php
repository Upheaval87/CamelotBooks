<?php

namespace App\Mail;

use App\Models\SalesReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SalesReceipt $receipt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sales Receipt {$this->receipt->receipt_number}",
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
        $r = $this->receipt;
        $customerName = $r->customer->name ?? 'Walk-in Customer';
        $companyName = $r->company->name ?? 'Your Company';
        $total = number_format($r->total, 2);
        $lines = $r->lines;

        $linesHtml = '';
        foreach ($lines as $line) {
            $linesHtml .= "<tr>
                <td style='padding:8px;border:1px solid #ddd;'>{$line->description}</td>
                <td style='padding:8px;border:1px solid #ddd;text-align:right;'>{$line->quantity}</td>
                <td style='padding:8px;border:1px solid #ddd;text-align:right;'>{$line->unit_price}</td>
                <td style='padding:8px;border:1px solid #ddd;text-align:right;'>{$line->line_total}</td>
            </tr>";
        }

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Sales Receipt {$r->receipt_number}</h2>
            <p>Dear {$customerName},</p>
            <p><strong>Date:</strong> {$r->receipt_date?->format('M d, Y')}</p>
            <table style="width:100%;border-collapse:collapse;margin:16px 0;">
                <thead><tr style="background:#f5f5f5;">
                    <th style="padding:8px;border:1px solid #ddd;text-align:left;">Description</th>
                    <th style="padding:8px;border:1px solid #ddd;text-align:right;">Qty</th>
                    <th style="padding:8px;border:1px solid #ddd;text-align:right;">Price</th>
                    <th style="padding:8px;border:1px solid #ddd;text-align:right;">Total</th>
                </tr></thead>
                <tbody>{$linesHtml}</tbody>
            </table>
            <p style="text-align:right;font-size:16px;"><strong>Total: {$total}</strong></p>
            <p>Thank you for your purchase!</p>
            <br/>
            <p>Regards,<br/>{$companyName}</p>
        </div>
        HTML;
    }
}
