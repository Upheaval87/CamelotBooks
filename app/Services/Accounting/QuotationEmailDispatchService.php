<?php

namespace App\Services\Accounting;

use App\Jobs\SendQuotationEmail;
use App\Models\AuditLog;
use App\Models\Quotation;
use App\Models\QuotationEmailJob;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * §2 EMAIL DISPATCH — the server half of the four-stage progress subsystem.
 *
 * Every state the modal/dock can display is written here as a durable row so
 * the client never has to guess: the job row carries `stage`/`status` and the
 * quotation carries `email_status`/`fail_reason`. Success and failure both
 * write an audit row, so the register's existing needs-attention rail (which
 * derives from the `email_failed` action) keeps working unchanged.
 */
class QuotationEmailDispatchService
{
    /**
     * §2.3 — validate the recipient, create the job row, then queue the
     * pipeline. The dispatch runs AFTER the 202 response has been flushed
     * (`dispatchAfterResponse`), so the browser observes genuine stage events
     * while the mail is actually being prepared and delivered. With
     * `QUEUE_CONNECTION=sync` this still goes down the queue path, just
     * post-response; with a real worker it is an ordinary queued job.
     */
    public function start(Quotation $quotation, string $recipient, ?int $userId, bool $isRetry = false): QuotationEmailJob
    {
        $job = QuotationEmailJob::create([
            'company_id' => $quotation->company_id,
            'quotable_type' => Quotation::class,
            'quotable_id' => $quotation->id,
            'recipient' => $recipient,
            'stage' => 'preparing',
            'status' => QuotationEmailJob::STATUS_RUNNING,
            'attempts' => $isRetry
                ? ((int) $quotation->emailJobs()->max('attempts') + 1)
                : 1,
            'dispatched_by' => $userId,
        ]);

        try {
            app(Dispatcher::class)->dispatchAfterResponse(new SendQuotationEmail($job->id, (int) $quotation->company_id));
        } catch (\Throwable $e) {
            // The job row is already the source of truth for the UI; if the bus
            // refused it (no queue configured) fall back to recording the
            // failure rather than leaving a job spinning forever.
            Log::warning('Quotation email dispatch could not be queued.', [
                'email_job_id' => $job->id,
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($job, $e);
        }

        return $job->refresh();
    }

    /** §3.2 — advance the pipeline marker the progress bar reads. */
    public function stage(QuotationEmailJob $job, string $stage): QuotationEmailJob
    {
        $job->forceFill([
            'stage' => $stage,
            'status' => QuotationEmailJob::STATUS_RUNNING,
        ])->save();

        return $job;
    }

    /**
     * §4.4 — cancellation is recorded on the job only. The quotation keeps its
     * previous state untouched, exactly as the mid-send cancel copy promises.
     */
    public function cancel(QuotationEmailJob $job): QuotationEmailJob
    {
        if ($job->status !== QuotationEmailJob::STATUS_RUNNING) {
            return $job;
        }

        $job->forceFill(['cancel_requested_at' => now()])->save();

        return $job;
    }

    /** Read cancellation straight from the row — the pipeline polls this. */
    public function isCancelled(QuotationEmailJob $job): bool
    {
        return $job->newQuery()
            ->whereKey($job->getKey())
            ->whereNotNull('cancel_requested_at')
            ->exists();
    }

    /**
     * §2.1 success — stamp the quotation, clear any previous failure, and audit.
     * The audit action/shape matches the existing `emailed` row written by
     * QuotationService::recordEmailed() so nothing downstream changes.
     */
    public function succeed(QuotationEmailJob $job, ?int $actorId = null): QuotationEmailJob
    {
        $quotation = $job->quotable;

        if ($quotation) {
            $previous = array_filter([
                'emailed_at' => $quotation->emailed_at,
                'emailed_to' => $quotation->emailed_to,
                'email_status' => $quotation->email_status,
            ], fn ($value) => $value !== null);

            $quotation->forceFill([
                'emailed_at' => now(),
                'emailed_to' => $job->recipient,
                'email_status' => 'sent',
                'fail_reason' => null,
            ])->saveQuietly();

            AuditLog::log(
                $quotation->company_id,
                $actorId ?? $job->dispatched_by,
                Quotation::class,
                $quotation->id,
                'emailed',
                $previous ?: null,
                [
                    'emailed_at' => now()->toDateTimeString(),
                    'emailed_to' => $job->recipient,
                    'email_status' => 'sent',
                ],
                'Quotation PDF dispatched by the email dispatch pipeline.'
            );
        }

        $job->forceFill([
            'status' => QuotationEmailJob::STATUS_SUCCEEDED,
            'stage' => 'recording',
            'error_code' => null,
            'error_message' => null,
        ])->save();

        return $job;
    }

    /**
     * §4.3 — failure retracts the "emailed" stamp (an undelivered quotation must
     * never look sent), records the SMTP code + message, and audits. The audit
     * payload mirrors QuotationMail::failed() so the register rail keeps
     * deriving failures from the same action it always did.
     */
    public function fail(QuotationEmailJob $job, \Throwable $e, ?int $actorId = null): QuotationEmailJob
    {
        [$code, $message] = $this->reasonFor($e);
        $quotation = $job->quotable;

        if ($quotation) {
            // Capture the retracted values BEFORE clearing them: forceFill()
            // mutates the in-memory model.
            $retracted = array_filter([
                'emailed_at' => $quotation->emailed_at,
                'emailed_to' => $quotation->emailed_to,
                'email_status' => $quotation->email_status,
            ], fn ($value) => $value !== null);

            $quotation->forceFill([
                'emailed_at' => null,
                'emailed_to' => null,
                'email_status' => 'failed',
                'fail_reason' => $message,
            ])->saveQuietly();

            AuditLog::log(
                $quotation->company_id,
                $actorId ?? $job->dispatched_by,
                Quotation::class,
                $quotation->id,
                'email_failed',
                $retracted ?: null,
                [
                    'emailed_at' => null,
                    'emailed_to' => null,
                    'email_status' => 'failed',
                ],
                $message
            );
        }

        $job->forceFill([
            'status' => QuotationEmailJob::STATUS_FAILED,
            'error_code' => $code,
            'error_message' => $message,
        ])->save();

        return $job;
    }

    /**
     * §4.3 — "SMTP code + message". Transport exceptions carry the SMTP reply
     * either as a numeric code or as the leading digits of the message, so both
     * are harvested before the message is stored on `fail_reason`.
     *
     * @return array{0: string|null, 1: string}
     */
    public function reasonFor(\Throwable $e): array
    {
        $message = trim($e->getMessage()) ?: 'Mail delivery failed.';

        $code = null;
        if ($e->getCode() > 0) {
            $code = (string) $e->getCode();
        } elseif (preg_match('/\b(\d{3})\b/', $message, $m)) {
            $code = $m[1];
        }

        // fail_reason is a 255-char column; keep the code at the front so the
        // register chip and the result card can both show it.
        $reason = $code ? $code . ' — ' . $message : $message;
        $reason = mb_substr($reason, 0, 255);

        return [$code, $reason];
    }
}