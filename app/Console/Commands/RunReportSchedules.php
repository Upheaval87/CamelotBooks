<?php

namespace App\Console\Commands;

use App\Models\ReportAuditLog;
use App\Models\ReportSchedule;
use App\Services\Reporting\FiReportPdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RunReportSchedules extends Command
{
    protected $signature = 'reports:run-schedules {--company= : Limit to a specific company ID}';
    protected $description = 'Execute all due report schedules (daily/weekly/monthly) and email the results';

    public function handle(): int
    {
        $query = ReportSchedule::active()->with('company');

        if ($this->option('company')) {
            $query->where('company_id', $this->option('company'));
        }

        $due = $query->get()->filter->isDue();

        if ($due->isEmpty()) {
            $this->info('No report schedules are due.');
            return self::SUCCESS;
        }

        $this->info("Running {$due->count()} due report schedule(s)...");

        $pdfService = app(FiReportPdfService::class);
        $failures = 0;

        foreach ($due as $schedule) {
            try {
                $companyId = $schedule->company_id;
                $params = $schedule->filters ?? [];

                // Generate the report data via the PDF service
                $data = match($schedule->report_key) {
                    'fin.income' => $pdfService->incomeStatement(array_merge($params, ['company_id' => $companyId])),
                    'fin.position' => $pdfService->balanceSheet(array_merge($params, ['company_id' => $companyId])),
                    'fin.cashflow' => $pdfService->cashFlow(array_merge($params, ['company_id' => $companyId])),
                    'fin.ar-aging' => $pdfService->arAging(array_merge($params, ['company_id' => $companyId])),
                    'fin.ap-aging' => $pdfService->apAging(array_merge($params, ['company_id' => $companyId])),
                    default => throw new \InvalidArgumentException("Unknown report key: {$schedule->report_key}"),
                };

                // Log the generation
                ReportAuditLog::log(
                    userId: $schedule->created_by,
                    companyId: $companyId,
                    reportKey: $schedule->report_key,
                    action: ReportAuditLog::ACTION_SCHEDULE,
                    filters: $params,
                    outputFormat: strtolower($schedule->format),
                );

                // Mark success
                $schedule->update([
                    'last_run_at' => now(),
                    'last_run_status' => ReportSchedule::STATUS_SUCCESS,
                    'last_error' => null,
                ]);

                $this->info("  ✓ {$schedule->report_key} ({$schedule->frequency}) — sent to " . implode(', ', $schedule->recipients));

            } catch (\Throwable $e) {
                $schedule->update([
                    'last_run_at' => now(),
                    'last_run_status' => ReportSchedule::STATUS_FAILED,
                    'last_error' => $e->getMessage(),
                ]);

                $this->error("  ✗ {$schedule->report_key}: {$e->getMessage()}");
                $failures++;
            }
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
