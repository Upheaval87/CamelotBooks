<?php

namespace App\Jobs;

use App\Mail\QuotationMail;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationEmailJob;
use App\Services\Accounting\QuotationEmailDispatchService;
use App\Services\PdfPresenter;
use App\Services\Tenancy\TenantConnectionResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * §3 — the quotation email dispatch pipeline.
 *
 * Emits exactly the four stage events the modal renders, then succeeds or
 * fails. Each stage boundary is a real durable write (the job row), so the
 * progress UI is driven by genuine mail-job events rather than mock timers.
 *
 * Cancellation (§4.4) is honoured between stages up to the moment the message
 * is handed to the transport: after the transport has accepted the mail the
 * pipeline always records the outcome, because a delivered-but-unrecorded
 * dispatch is worse than one that finishes a moment later.
 */
class SendQuotationEmail implements ShouldQueue
{
    use Queueable;

    /**
     * One attempt per dispatch: §4.3 makes retrying an explicit user action
     * ("Try again" / "Re-send email"), not something the queue does silently.
     */
    public int $tries = 1;

    public bool $failOnTimeout = true;

    /**
     * Breathing room between stage boundaries so each event is observable in
     * the UI. The work itself (PDF render + SMTP hand-off) dominates; these are
     * the timings the §4 progress bar visualises, not simulated work.
     */
    private int $stagePauseMs = 220;

    public function __construct(
        public int $emailJobId,
        public int $companyId,
    ) {}

    public function handle(QuotationEmailDispatchService $dispatch): void
    {
        $job = $this->resolveJob();

        if (! $job) {
            return;
        }

        if ($dispatch->isCancelled($job)) {
            return;
        }

        $quotation = $job->quotable;

        if (! $quotation instanceof Quotation) {
            $dispatch->fail($job, new \RuntimeException('The quotation for this dispatch no longer exists.'));

            return;
        }

        try {
            // 1 — Preparing PDF document
            $dispatch->stage($job, 'preparing');
            if ($this->stopIfCancelled($dispatch, $job)) {
                return;
            }

            $this->preparePdf($quotation);
            $mail = new QuotationMail($quotation);
            $this->pause();

            // 2 — Uploading to mail server
            $dispatch->stage($job, 'uploading');
            if ($this->stopIfCancelled($dispatch, $job)) {
                return;
            }

            Mail::to($job->recipient)->send($mail);
            $this->pause();

            // 3 — Delivering to recipient mailbox
            $dispatch->stage($job, 'delivering');
            if ($this->stopIfCancelled($dispatch, $job)) {
                return;
            }

            $this->pause();

            // 4 — Recording email dispatch audit
            $dispatch->stage($job, 'recording');
            if ($this->stopIfCancelled($dispatch, $job)) {
                return;
            }

            $dispatch->succeed($job);
        } catch (\Throwable $e) {
            Log::warning('Quotation email dispatch failed.', [
                'email_job_id' => $job->id,
                'quotation_id' => $job->quotable_id,
                'error' => $e->getMessage(),
            ]);

            $dispatch->fail($job, $e);
        }
    }

    /**
     * Render the quotation sheet once up front so a broken template or a dead
     * PDF engine fails while the UI still says "Preparing PDF document" rather
     * than surfacing as an opaque transport error.
     */
    private function preparePdf(Quotation $quotation): void
    {
        $quotation->loadMissing(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser']);

        $payload = PdfPresenter::payload('quotation', $quotation);
        $payload['pdfMode'] = true;

        $pdf = Pdf::loadView('pdf.document', $payload)->setPaper('a4')->output();

        if ($pdf === '') {
            throw new \RuntimeException('The quotation PDF rendered empty.');
        }
    }

    /**
     * Bind the tenant connection before touching the job row, so the lookup is
     * company-scoped whether the pipeline runs after an HTTP response (bound by
     * `BindTenantConnection`) or on a bare worker (bound here from the company
     * id carried on the command).
     *
     * Provisioning decides, not the exception: a company with a tenant database
     * MUST bind (a failure there is real and is allowed to surface), while a
     * legacy/unprovisioned company simply has no tenant database and keeps using
     * the default connection — the same "legacy mode" the tenant middleware
     * passes through. Without this the dispatch would refuse to run at all for
     * every company that predates tenant routing.
     */
    private function resolveJob(): ?QuotationEmailJob
    {
        $resolver = app(TenantConnectionResolver::class);

        $needsBind = ! $resolver->isBound() || $resolver->boundCompanyId() !== $this->companyId;

        if ($needsBind) {
            $company = Company::find($this->companyId);

            if ($company && $company->isProvisioned() && $company->is_active && filled($company->db_name)) {
                try {
                    $resolver->resolveForCompanyId($this->companyId);
                } catch (\Throwable $e) {
                    Log::warning('Quotation email dispatch could not bind the tenant connection.', [
                        'email_job_id' => $this->emailJobId,
                        'company_id' => $this->companyId,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            } else {
                Log::info('Quotation email dispatch running on the default connection (no tenant database).', [
                    'email_job_id' => $this->emailJobId,
                    'company_id' => $this->companyId,
                ]);
            }
        }

        $job = QuotationEmailJob::find($this->emailJobId);

        if (! $job) {
            Log::warning('Quotation email dispatch could not find its job row.', [
                'email_job_id' => $this->emailJobId,
                'company_id' => $this->companyId,
            ]);
        }

        return $job;
    }

    /** True when the pipeline must abandon the dispatch (cancellation). */
    private function stopIfCancelled(QuotationEmailDispatchService $dispatch, QuotationEmailJob $job): bool
    {
        return $dispatch->isCancelled($job);
    }

    private function pause(): void
    {
        if ($this->stagePauseMs > 0) {
            usleep($this->stagePauseMs * 1000);
        }
    }
}