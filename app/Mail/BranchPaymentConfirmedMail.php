<?php

namespace App\Mail;

use App\Models\BranchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchPaymentConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public BranchRequest $request,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment confirmed - branch request fulfilled: {$this->request->branch_name}",
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
        $r = $this->request;
        $companyName = $r->company->name ?? 'Your Company';
        $quotation = $r->quotation;
        $newLimit = $r->company->branch_limit === null
            ? 'unlimited'
            : number_format($r->company->branch_limit);
        $quotationNumber = $quotation?->quotation_number ?? 'N/A';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Payment Confirmed</h2>
            <p>Your payment for quotation <strong>{$quotationNumber}</strong> has been confirmed. Your branch request for <strong>{$r->branch_name}</strong> is fulfilled.</p>
            <p>Your branch limit is now <strong>{$newLimit}</strong>. You can create the new branch from the Branches screen.</p>
            <p>Regards,<br/>{$companyName}</p>
        </div>
        HTML;
    }
}
