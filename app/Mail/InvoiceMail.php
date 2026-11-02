<?php

namespace App\Mail;

use App\Models\AccountAuditLog;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\Invoice;
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
 * INVOICES MODULE R17 — "Send to customer".
 *
 * The tax-invoice PDF is produced by the same server-rendered pipeline the print
 * endpoint streams, so the emailed sheet and the printed sheet are byte-for-byte
 * the same document. If every delivery attempt fails the emailed stamp is
 * retracted, mirroring `QuotationMail`: a failed dispatch must never be left
 * recorded as delivered (5.5).
 */
class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public bool $failOnTimeout = true;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderTemplate()['subject']);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->buildBody());
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

    public function failed(?\Throwable $exception): void
    {
        $message = $exception ? $exception->getMessage() : 'Mail delivery failed.';

        // Read the values BEFORE forceFill(): it mutates the in-memory model, so
        // capturing afterwards would audit a null that was never on the row.
        $retracted = array_filter([
            'emailed_at' => $this->invoice->emailed_at,
            'emailed_to' => $this->invoice->emailed_to,
        ], fn ($value) => $value !== null);

        $this->invoice->forceFill([
            'emailed_at' => null,
            'emailed_to' => null,
        ])->saveQuietly();

        // 5.5 — a failed dispatch is an invoice mutation, so it is written to the same
        // ledger trail `ArInvoiceService` uses rather than the general `audit_logs`
        // table; the email's success and failure rows then sit beside the post row
        // in one chronological history for the document.
        AccountAuditLog::create([
            'company_id' => $this->invoice->company_id,
            'journalable_type' => Invoice::class,
            'journalable_id' => $this->invoice->id,
            'action' => 'email_failed',
            'old_values' => $retracted ?: null,
            'new_values' => ['emailed_at' => null, 'emailed_to' => null],
            'user_id' => $this->invoice->posted_by ?? $this->invoice->created_by,
            'notes' => $message,
            'created_at' => now(),
        ]);
    }

    public function pdfFilename(): string
    {
        return 'invoice-' . $this->invoice->invoice_number . '.pdf';
    }

    /**
     * Worker-safe currency formatting: the symbol is resolved from the central
     * currency catalog rather than the session, so a queued send renders the
     * same figures as the sheet.
     */
    protected function money(float|int|string|null $amount): string
    {
        $code = $this->invoice->currency ?: $this->invoice->company?->base_currency;
        $symbol = ($code ? Currency::query()->where('code', $code)->value('symbol') : null) ?: $code;

        return trim(($symbol ? $symbol . ' ' : '') . format_number((float) $amount));
    }

    protected function renderPdf(): string
    {
        $invoice = $this->invoice->loadMissing([
            'lines.product', 'customer', 'branch', 'sourceQuotation', 'payments',
        ]);

        $payload = PdfPresenter::payload('invoice', $invoice);
        $payload['pdfMode'] = true;

        return Pdf::loadView('pdf.document', $payload)->setPaper('a4')->output();
    }

    /**
     * @return array{subject: string, body: string}
     */
    protected function renderTemplate(): array
    {
        $invoice = $this->invoice->loadMissing(['customer', 'company']);
        $company = $invoice->company;

        $template = EmailTemplate::where('event_type', 'invoice_sent')
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

        $defaults = EmailTemplate::defaultTemplates()['invoice_sent'] ?? [
            'subject' => 'Invoice {{invoice_number}} from {{company_name}}',
            'body' => "Dear {{customer_name}},\n\nPlease find attached invoice {{invoice_number}}.\n\nTotal due: {{total_amount}}\nDue: {{due_date}}\n\n{{company_name}}",
        ];

        $subject = $template?->subject ?: $defaults['subject'];
        $body = $template?->body ?: $defaults['body'];

        $values = [
            '{{invoice_number}}' => (string) $invoice->invoice_number,
            '{{company_name}}' => (string) ($company->name ?? config('app.name')),
            '{{customer_name}}' => (string) $invoice->partyName(),
            '{{contact_name}}' => (string) ($invoice->contact_person ?? ''),
            '{{invoice_date}}' => $invoice->issueDate()?->format('M d, Y') ?? '',
            '{{due_date}}' => $invoice->due_date?->format('M d, Y') ?? '',
            '{{subtotal}}' => $this->money($invoice->documentSubtotal()),
            '{{tax_total}}' => $this->money($invoice->documentTax()),
            '{{total_amount}}' => $this->money($invoice->documentTotal()),
            '{{balance_due}}' => $this->money($invoice->balance_due),
            '{{currency}}' => (string) ($invoice->currency ?: ''),
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
        $invoice = $this->invoice->loadMissing(['lines', 'customer', 'company', 'payments']);
        $rendered = $this->renderTemplate();
        $companyName = $invoice->company?->name ?? config('app.name');
        $number = e((string) $invoice->invoice_number);

        $rows = '';
        foreach ($invoice->lines as $line) {
            $label = trim(implode(' — ', array_filter(array_filter([
                $line->item_code,
                $line->item_name,
                $line->description,
            ], fn ($part) => $part !== null && $part !== ''))));

            $rows .= '<tr>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;">' . e($label) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;text-align:right;">' . e((string) $line->quantity) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;text-align:right;">' . e($this->money($line->unit_price)) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe6e6;text-align:right;">' . e($this->money($line->line_total)) . '</td>'
                . '</tr>';
        }

        $notes = $invoice->notes_customer
            ? '<p style="margin:16px 0 0;"><strong>Notes:</strong> ' . nl2br(e($invoice->notes_customer)) . '</p>'
            : '';

        // R16 — the payments-received strip only renders once money has moved.
        $payments = '';
        if ($invoice->payments->isNotEmpty()) {
            $payments = '<table style="width:100%;border-collapse:collapse;margin:18px 0;">'
                . '<thead><tr style="background:#0E7473;color:#fff;">'
                . '<th style="padding:7px;border:1px solid #0E7473;text-align:left;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Receipt</th>'
                . '<th style="padding:7px;border:1px solid #0E7473;text-align:left;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Date</th>'
                . '<th style="padding:7px;border:1px solid #0E7473;text-align:left;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Method</th>'
                . '<th style="padding:7px;border:1px solid #0E7473;text-align:right;font-size:11px;letter-spacing:.1em;text-transform:uppercase;">Amount</th>'
                . '</tr></thead><tbody>';

            foreach ($invoice->payments as $payment) {
                $payments .= '<tr>'
                    . '<td style="padding:7px;border:1px solid #dfe6e6;font-family:ui-monospace,Menlo,Consolas,monospace;">' . e((string) $payment->receipt_no) . '</td>'
                    . '<td style="padding:7px;border:1px solid #dfe6e6;">' . e($payment->paid_on?->format('M d, Y') ?? '') . '</td>'
                    . '<td style="padding:7px;border:1px solid #dfe6e6;">' . e($payment->methodName()) . '</td>'
                    . '<td style="padding:7px;border:1px solid #dfe6e6;text-align:right;">' . e($this->money($payment->amount)) . '</td>'
                    . '</tr>';
            }

            $payments .= '</tbody></table>';
        }

        $balance = $invoice->balance_due;
        $flag = $balance <= 0.005
            ? '<div style="margin-top:14px;padding:10px 12px;background:#E8F6EF;border:1px solid #b9e0c9;font-weight:800;color:#14663f;">PAID IN FULL</div>'
            : '<div style="margin-top:14px;padding:10px 12px;background:#FDF3E3;border:1px solid #ecd3a4;font-weight:800;color:#7a4a06;">BALANCE DUE: ' . e($this->money($balance)) . ' — due ' . e($invoice->due_date?->format('M d, Y') ?? 'on receipt') . '</div>';

        $intro = nl2br(e($rendered['body']));

        // Precomputed for the heredoc: PHP interpolation cannot call methods
        // inside `{$...}`, so these must be resolved beforehand.
        $invoiceTotal = e($this->money($invoice->documentTotal()));

        return <<<HTML
        <div style="font-family:Inter,'Segoe UI',Arial,system-ui,sans-serif;max-width:640px;margin:0 auto;color:#0B2A2D;">
            <div style="border-bottom:3px solid #128F8E;padding-bottom:12px;margin-bottom:20px;">
                <div style="font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#128F8E;font-weight:800;">Tax Invoice</div>
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
                <span style="color:#5F7476;font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:800;">Invoice total</span>
                <div style="font-weight:800;">{$invoiceTotal}</div>
            </div>

            {$flag}
            {$payments}
            {$notes}

            <p style="margin:22px 0 0;color:#5F7476;font-size:12px;">
                The full tax invoice is attached as {$number}.pdf.
            </p>

            <p style="margin:24px 0 0;color:#8AA5A7;font-size:11px;">
                This message was sent by {$companyName} on behalf of the sender.
            </p>
        </div>
        HTML;
    }
}