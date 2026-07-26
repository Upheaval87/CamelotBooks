<?php

namespace App\Console\Commands;

use App\Mail\ExecutiveDigestMail;
use App\Models\BiDigestSchedule;
use App\Services\BI\ExecutiveDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExecutiveDigest extends Command
{
    protected $signature = 'bi:send-digest {--dry-run : Preview without sending}';

    protected $description = 'Send executive digest emails to all active schedule recipients';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $service = new ExecutiveDigestService();

        $schedules = BiDigestSchedule::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_sent_at')
                    ->orWhere(function ($q2) {
                        $q2->where('frequency', BiDigestSchedule::FREQUENCY_DAILY)
                            ->where('last_sent_at', '<', now()->subDay());
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('frequency', BiDigestSchedule::FREQUENCY_WEEKLY)
                            ->where('last_sent_at', '<', now()->subWeek());
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('frequency', BiDigestSchedule::FREQUENCY_MONTHLY)
                            ->where('last_sent_at', '<', now()->subMonth());
                    });
            })
            ->with('company')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No digest schedules due for sending.');
            return static::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            $companyName = $schedule->company->name ?? 'Unknown Company';
            $recipients = $schedule->recipients ?? [];

            if (empty($recipients)) {
                $this->warn("  Skipping schedule #{$schedule->id} ({$companyName}) — no recipients.");
                continue;
            }

            $this->info("  Processing digest for {$companyName} ({$schedule->frequency})...");

            try {
                $digest = $service->collectForSchedule($schedule);

                if ($dryRun) {
                    $this->info("  [DRY RUN] Would send to: " . implode(', ', $recipients));
                    $this->line("    Revenue: " . format_money($digest['revenue']));
                    $this->line("    Expenses: " . format_money($digest['expenses']));
                    $this->line("    Net Income: " . format_money($digest['net_income']));
                    $sent++;
                    continue;
                }

                foreach ($recipients as $email) {
                    Mail::to($email)->send(new ExecutiveDigestMail($digest, $companyName));
                }

                $schedule->update(['last_sent_at' => now()]);
                $this->info("  Sent to " . count($recipients) . " recipient(s).");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  FAILED for {$companyName}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Complete: {$sent} sent, {$failed} failed.");

        return $failed > 0 ? static::FAILURE : static::SUCCESS;
    }
}
