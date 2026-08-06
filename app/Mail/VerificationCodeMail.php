<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class VerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $maskedEmail,
        public Carbon $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your CamelotBooks verification code',
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
        $minutes = (int) max(1, $this->expiresAt->diffInMinutes(now()));

        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#333">
            <div style="padding:24px 32px;background:#0E1526;border-radius:8px 8px 0 0">
                <span style="color:#A47C2B;font-size:14px;letter-spacing:0.08em">CAMELOTBOOKS</span>
            </div>
            <div style="padding:32px;background:#FFFFFF;border:1px solid #E3E6EB;border-top:0;border-radius:0 0 8px 8px">
                <h2 style="color:#101828;margin:0 0 8px">Your verification code</h2>
                <p style="color:#667085;margin:0 0 24px">Enter this 6-digit code to finish resetting your password for <strong>{$this->maskedEmail}</strong>.</p>
                <p style="font-family:Consolas,Monaco,'Courier New',monospace;font-size:36px;font-weight:700;letter-spacing:12px;color:#0E1526;background:#F7F8FA;border:1px dashed #D0D5DD;border-radius:8px;text-align:center;padding:20px;margin:0 0 24px">{$this->code}</p>
                <p style="color:#667085;margin:0 0 8px">This code expires in <strong>{$minutes} minutes</strong>. If it expires, request a new one.</p>
                <p style="color:#98A2B3;font-size:12px;margin:24px 0 0;border-top:1px solid #E3E6EB;padding-top:16px">If you didn't request a password reset, you can safely ignore this email. Your password won't change.</p>
            </div>
        </div>
        HTML;
    }
}
