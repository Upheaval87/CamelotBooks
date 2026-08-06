<?php

namespace App\Mail;

use App\Models\BranchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchRequestRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public BranchRequest $request,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Branch request rejected: {$this->request->branch_name}",
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
        $reasonLine = $r->admin_notes ? "<p><strong>Reason:</strong> {$r->admin_notes}</p>" : '';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Branch Request Rejected</h2>
            <p>Your branch request for <strong>{$r->branch_name}</strong> was not approved.</p>
            {$reasonLine}
            <p>You may submit a new request at any time.</p>
            <p>Regards,<br/>{$companyName}</p>
        </div>
        HTML;
    }
}
