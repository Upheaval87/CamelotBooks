<?php

namespace App\Mail;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\Quotation;
use App\Services\PdfPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * §6 EMAIL DISPATCH — the quotation sheet PDF is attached from the same
 * server-rendered pipeline the "Save as PDF" endpoint streams, the subject and
 * body come from the company standard `quotation_sent` template, and a final
 * failure clears emailed_at/emailed_to so a failed dispatch is never recorded
 * as delivered (§6.3).
 */
class QuotationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** §6.3 — retry policy: three attempts with a widening backoff. */
    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public bool $failOnTimeout = true;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderTemplate()['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildBody(),
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->renderPdf(), $this->pdfFilename())
                ->withMime('application/pdf'),
        ];
    }

    /**
     * §6.3 — when every attempt has failed, retract the "emailed" stamp and
     * leave a trail the register can surface in its needs-attention rail.
     */
    public function failed(?\Throwable $exception): void
    {
        $message = $exception ? $exception->getMessage() : 'Mail delivery failed.';

        // Capture the retracted values BEFORE clearing them: forceFill() mutates
        // the in-memory model, so reading them afterwards would audit a null that
        // was never on the row.
        $retracted = array_filter([
            'emailed_at' => $this->quotation->emailed_at,
            'emailed_to' => $this->quotation->emailed_to,
        ], fn ($value) => $value !== null);

        $this->quotation->forceFill([
            'emailed_at' => null,
            'emailed_to' => null,
        ])->saveQuietly();

        AuditLog::log(
            $this->quotation->company_id,
            $this->quotation->created_by,
            Quotation::class,
            $this->quotation->id,
            'email_failed',
            $retracted ?: null,
            ['emailed_at' => null, 'emailed_to' => null],
            $message
        );
    }

    public function pdfFilename(): string
    {
        return 'quotation-' . $this->quotation->quotation_number . '.pdf';
    }

    /**
     * Worker-safe currency formatting: the base-currency symbol is resolved from
     * the central currency catalog rather than the session, so a queued send
     * renders the same figures as the sheet.
     */
    protected function money(float|int|string|null $amount): string
    {
        $code = $this->quotation->currency ?: $this->quotation->company?->base_currency;
        $symbol = ($code ? Currency::query()->where('code', $code)->value('symbol') : null) ?: $code;

        return trim(($symbol ? $symbol . ' ' : '') . format_number((float) $amount));
    }

    /**
     * The same server-rendered sheet the PDF endpoint streams.
     */
    protected function renderPdf(): string
    {
        $quotation = $this->quotation->loadMissing(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser']);

        $payload = PdfPresenter::payload('quotation', $quotation);
        $payload['pdfMode'] = true;

        return Pdf::loadView('pdf.document', $payload)->setPaper('a4')->output();
    }

    /**
     * Resolve the company standard template and interpolate its placeholders.
     *
     * @return array{subject: string, body: string}
     */
    protected function renderTemplate(): array
    {
        $quotation = $this->quotation->loadMissing(['customer', 'company']);
        $company = $quotation->company;

        $template = EmailTemplate::where('event_type', 'quotation_sent')
            ->where(function ($q) use ($company) {
                $q->where('company_id', $company?->id)->orWhereNull('company_id');
            })
            // A template an administrator switched off must not reach a customer.
            // Rows written before the column existed hold NULL — treat those as on.
            ->where(function ($q) {
                $q->where('is_enabled', true)->orWhereNull('is_enabled');
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->first();

        $defaults = EmailTemplate::defaultTemplates()['quotation_sent'] ?? [
            'subject' => 'Quotation {{quotation_number}} from {{company_name}}',
            'body' => "Dear {{customer_name}},\n\nPlease find attached quotation {{quotation_number}}.\n\nTotal: {{total_amount}}\n\n{{company_name}}",
        ];

        $subject = $template?->subject ?: $defaults['subject'];
        $body = $template?->body ?: $defaults['body'];

        $values = [
            '{{quotation_number}}' => (string) $quotation->quotation_number,
            '{{company_name}}' => (string) ($company->name ?? config('app.name')),
            '{{customer_name}}' => (string) ($quotation->customer?->name ?? $quotation->customer_name ?? 'Valued Customer'),
            '{{contact_name}}' => (string) ($quotation->contact_person ?? ''),
            '{{quotation_date}}' => $quotation->quotation_date?->format('M d, Y') ?? '',
            '{{valid_until}}' => $quotation->valid_until?->format('M d, Y') ?? '',
            '{{total_amount}}' => $this->money($quotation->total),
            '{{currency}}' => (string) ($quotation->currency ?: ''),
        ];

        $search = array_keys($values);
        $replace = array_map(fn ($value) => e($value), $values);

        return [
            'subject' => str_replace($search, $replace, $subject),
            'body' => str_replace($search, $replace, $body),
        ];
    }

    protected function buildBody(): string
    {
        $quotation = $this->quotation->loadMissing(['lines', 'customer', 'company']);
        $rendered = $this->renderTemplate();
        $companyName = $quotation->company->name ?? config('app.name');
        $total = $this->money($quotation->total);

        $rows = '';
        foreach ($quotation->lines as $line) {
            $rows .= '<tr>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;">' . e($line->description) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;text-align:right;">' . e((string) $line->quantity) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;text-align:right;">' . e($this->money($line->unit_price)) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;text-align:right;">' . e($this->money($line->line_total)) . '</td>'
                . '</tr>';
        }

        $memo = $quotation->memo
            ? '<p style="margin:16px 0 0;"><strong>Notes:</strong> ' . nl2br(e($quotation->memo)) . '</p>'
            : '';

        $intro = nl2br(e($rendered['body']));
        $currency = e((string) ($quotation->currency ?: ''));
        $number = e((string) $quotation->quotation_number);

        return <<<HTML
        <div style="font-family:Inter,'Segoe UI',Arial,system-ui,sans-serif;max-width:640px;margin:0 auto;color:#0B2A2D;">
            <div style="border-bottom:3px solid #128F8E;padding-bottom:12px;margin-bottom:20px;">
                <div style="font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#128F8E;font-weight:800;">Quotation</div>
                <div style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:20px;font-weight:800;margin-top:4px;">{$number}</div>
            </div>

            <div style="white-space:normal;">{$intro}</div>

            <table style="width:100%;border-collapse:collapse;margin:20px 0;">
                <thead>
                    <tr style="background:#0E7473;color:#fff;">
                        <th style="padding:8px;border:1px solid #0E7473;text-align:left;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Description</th>
                        <th style="padding:8px;border:1px solid #0E7473;text-align:right;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Qty</th>
                        <th style="padding:8px;border:1px solid #0E7473;text-align:right;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Unit Price</th>
                        <th style="padding:8px;border:1px solid #0E7473;text-align:right;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Amount</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>

            <div style="text-align:right;font-size:16px;">
                <span style="color:#5F7476;font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:800;">Total</span>
                <div style="font-weight:800;">{$total}</div>
            </div>

            {$memo}

            <p style="margin:22px 0 0;color:#5F7476;font-size:12px;">
                The full quotation sheet is attached as {$number}.pdf.
            </p>

            <p style="margin:24px 0 0;color:#8AA5A7;font-size:11px;">
                This message was sent by {$companyName} on behalf of the sender.
            </p>
        </div>
        HTML;
    }
}