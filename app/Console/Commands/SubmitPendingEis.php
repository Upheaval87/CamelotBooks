<?php

namespace App\Console\Commands;

use App\Models\EisSubmission;
use App\Services\EIS\EisSubmissionService;
use Illuminate\Console\Command;

class SubmitPendingEis extends Command
{
    protected $signature = 'eis:submit-pending';
    protected $description = 'Retry pending and errored EIS submissions';

    public function handle(EisSubmissionService $eisService): int
    {
        $pendingCount = EisSubmission::where('status', EisSubmission::STATUS_PENDING)->count();
        $errorCount = EisSubmission::where('status', EisSubmission::STATUS_ERROR)
            ->where('retry_count', '<', 5)->count();

        $this->info("Found {$pendingCount} pending and {$errorCount} errored submissions.");

        if ($pendingCount === 0 && $errorCount === 0) {
            $this->info('Nothing to process.');
            return Command::SUCCESS;
        }

        $submissions = EisSubmission::whereIn('status', [EisSubmission::STATUS_PENDING, EisSubmission::STATUS_ERROR])
            ->where('retry_count', '<', 5)
            ->with('terminal')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $accepted = 0;
        $rejected = 0;
        $errors = 0;

        foreach ($submissions as $submission) {
            if (!$submission->terminal || !$submission->terminal->isActive()) {
                $this->warn("Skipping submission #{$submission->id}: terminal inactive.");
                $errors++;
                continue;
            }

            try {
                $result = $eisService->retrySubmission($submission);

                if ($result->status === EisSubmission::STATUS_ACCEPTED) {
                    $accepted++;
                    $this->info("Accepted: #{$submission->id} ({$submission->receipt_number})");
                } else {
                    $rejected++;
                    $this->warn("Rejected: #{$submission->id} ({$submission->receipt_number}) - {$result->error_message}");
                }
            } catch (\InvalidArgumentException $e) {
                $errors++;
                $this->error("Error: #{$submission->id} - {$e->getMessage()}");
            }
        }

        $this->info("Done. Accepted: {$accepted}, Rejected: {$rejected}, Errors: {$errors}");
        return Command::SUCCESS;
    }
}
