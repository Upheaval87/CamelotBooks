<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Quotation {$this->quotation->quotation_number}",
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
        $q = $this->quotation;
        $customerName = $q->customer->name ?? 'Valued Customer';
        $companyName = $q->company->name ?? 'Your Company';
        $validUntil = $q->valid_until ? $q->valid_until->format('M d, Y') : 'N/A';
        $total = number_format($q->total, 2);
        $lines = $q->lines;

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
            <h2 style="color: #333;">Quotation {$q->quotation_number}</h2>
            <p>Dear {$customerName},</p>
            <p>Please find below our quotation for your review.</p>
            <p><strong>Date:</strong> {$q->quotation_date?->format('M d, Y')}<br/>
            <strong>Valid Until:</strong> {$validUntil}</p>
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
            @if($q->memo)
                <p><strong>Memo:</strong> {$q->memo}</p>
            @endif
            <p>Please do not hesitate to contact us if you have any questions.</p>
            <br/>
            <p>Regards,<br/>{$companyName}</p>
        </div>
        HTML;
    }
}
