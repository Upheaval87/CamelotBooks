<?php

namespace App\Mail;

use App\Models\BillingQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchQuotationIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public BillingQuotation $quotation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Quotation {$this->quotation->quotation_number} for your branch request",
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
        $companyName = $q->company->name ?? 'Your Company';
        $branchName = $q->branchRequest->branch_name;
        $unitPrice = number_format($q->unit_price, 2);
        $subtotal = number_format($q->subtotal, 2);
        $total = number_format($q->total, 2);
        $validUntil = $q->valid_until?->format('M d, Y') ?? 'N/A';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Quotation {$q->quotation_number}</h2>
            <p>Your branch request for <strong>{$branchName}</strong> has been reviewed and approved.</p>
            <table style="width:100%;border-collapse:collapse;margin:16px 0;">
                <tr><td style="padding:8px;border:1px solid #ddd;">Unit price</td><td style="padding:8px;border:1px solid #ddd;text-align:right;">{$unitPrice}</td></tr>
                <tr><td style="padding:8px;border:1px solid #ddd;">Quantity</td><td style="padding:8px;border:1px solid #ddd;text-align:right;">{$q->quantity}</td></tr>
                <tr><td style="padding:8px;border:1px solid #ddd;">Subtotal</td><td style="padding:8px;border:1px solid #ddd;text-align:right;">{$subtotal}</td></tr>
                <tr><td style="padding:8px;border:1px solid #ddd;"><strong>Total ({$q->currency_code})</strong></td><td style="padding:8px;border:1px solid #ddd;text-align:right;"><strong>{$total}</strong></td></tr>
            </table>
            <p><strong>Valid until:</strong> {$validUntil}</p>
            <p><strong>Bank reference:</strong> {$q->bank_reference}</p>
            <p>Please record your payment (bank transfer, cheque, or cash) against reference <strong>{$q->bank_reference}</strong> from your Branch Requests screen. Once your payment is confirmed, your branch limit will be raised.</p>
            <p>Regards,<br/>{$companyName}</p>
        </div>
        HTML;
    }
}
