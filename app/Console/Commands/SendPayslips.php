<?php

namespace App\Console\Commands;

use App\Mail\PayslipMail;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayslipDelivery;
use App\Services\Payroll\EncryptedPayslipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPayslips extends Command
{
    protected $signature = 'payroll:send-payslips {runId : The payroll run ID} {--dry-run : Preview without sending}';

    protected $description = 'Send encrypted PDF payslips to all employees in a payroll run';

    public function handle(EncryptedPayslipService $payslipService): int
    {
        $runId = $this->argument('runId');
        $dryRun = $this->option('dry-run');

        $run = PayrollRun::with(['items.employee', 'company'])->findOrFail($runId);

        if (!in_array($run->status, [PayrollRun::STATUS_POSTED, PayrollRun::STATUS_PARTIALLY_PAID, PayrollRun::STATUS_FULLY_PAID])) {
            $this->error("Payroll run must be posted before sending payslips. Current status: {$run->status}");
            return static::FAILURE;
        }

        $items = $run->items()->with('employee')->get();
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $this->info("Processing {$items->count()} payslips for run {$run->run_number}...");

        foreach ($items as $item) {
            $employee = $item->employee;

            if (!$employee || !$employee->email) {
                $this->warn("  Skipping employee {$item->employee_id} — no email on file.");
                $skipped++;

                PayslipDelivery::updateOrCreate(
                    ['payroll_run_id' => $run->id, 'employee_id' => $item->employee_id],
                    [
                        'company_id' => $run->company_id,
                        'status' => PayslipDelivery::STATUS_FAILED,
                        'error_message' => 'No email address on file',
                    ]
                );
                continue;
            }

            $delivery = PayslipDelivery::updateOrCreate(
                ['payroll_run_id' => $run->id, 'employee_id' => $item->employee_id],
                [
                    'company_id' => $run->company_id,
                    'email_address' => $employee->email,
                    'status' => PayslipDelivery::STATUS_NOT_SENT,
                ]
            );

            if ($dryRun) {
                $this->info("  [DRY RUN] Would send to: {$employee->email} ({$employee->full_name})");
                $sent++;
                continue;
            }

            try {
                $pdfContent = $payslipService->generatePayslipPdf($run, $item);

                Mail::to($employee->email)
                    ->queue(new PayslipMail($run, $employee, $pdfContent));

                $delivery->update([
                    'status' => PayslipDelivery::STATUS_SENT,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                $this->info("  Sent to: {$employee->email} ({$employee->full_name})");
                $sent++;
            } catch (\Throwable $e) {
                $delivery->update([
                    'status' => PayslipDelivery::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);

                $this->error("  FAILED: {$employee->email} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Complete: {$sent} sent, {$failed} failed, {$skipped} skipped.");

        return $failed > 0 ? static::FAILURE : static::SUCCESS;
    }
}
