<?php

namespace App\Mail;

use App\Models\BranchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchRequestSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public BranchRequest $request,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New branch request: {$this->request->branch_name} ({$this->request->company->name})",
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
        $companyName = $r->company->name ?? 'A company';
        $branchCodeSuffix = $r->branch_code ? ' (' . e($r->branch_code) . ')' : '';
        $requestedAt = $r->requested_at?->format('M d, Y H:i') ?? 'N/A';
        $reasonLine = $r->reason ? "<p><strong>Reason:</strong> {$r->reason}</p>" : '';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">New Branch Request</h2>
            <p><strong>Company:</strong> {$companyName}<br/>
            <strong>Branch:</strong> {$r->branch_name}{$branchCodeSuffix}<br/>
            <strong>Quantity:</strong> {$r->requested_quantity}<br/>
            <strong>Requested:</strong> {$requestedAt}</p>
            {$reasonLine}
            <p>Review and issue a quotation from the Super Admin panel.</p>
        </div>
        HTML;
    }
}
