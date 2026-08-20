<?php

namespace App\Services\Payroll;

use App\Mail\PayslipMail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeePayslipSetting;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Payslip;
use App\Models\PayslipAuditLog;
use App\Models\PayslipDistribution;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PayslipDistributionService
{
    /**
     * Generate payslips for a finalized payroll run.
     * Creates Payslip records for each RunItem that doesn't already have one.
     */
    public function generatePayslips(PayrollRun $run, int $userId): array
    {
        $companyId = (int) $run->company_id;

        $items = PayrollRunItem::where('payroll_run_id', $run->id)->with('employee')->get();

        $generated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $existing = Payslip::where('payroll_run_id', $run->id)
                ->where('employee_id', $item->employee_id)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $payslipNumber = $this->generatePayslipNumber($companyId);
            $data = $item->payslip_data ?? [];

            $payslip = Payslip::create([
                'company_id'              => $companyId,
                'payroll_run_id'          => $run->id,
                'employee_id'             => $item->employee_id,
                'payslip_number'          => $payslipNumber,
                'status'                  => 'draft',
                'gross_pay'               => $item->gross_pay,
                'total_deductions'        => $item->total_deductions,
                'net_pay'                 => $item->net_pay,
                'earnings'                => $data['earnings'] ?? $this->buildEarnings($item),
                'deductions'              => $data['deductions'] ?? $this->buildDeductions($item),
                'employer_contributions'  => $data['employer_contributions'] ?? $this->buildEmployerContributions($item),
                'ytd_totals'              => $this->computeYtd($companyId, $item->employee_id, $item->gross_pay, $item->paye, $item->pension_ee),
            ]);

            $this->audit($payslip, $companyId, $item->employee_id, $userId, 'generated', [
                'payslip_number' => $payslipNumber,
            ]);

            $generated++;
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    /**
     * Finalize payslips — lock them from further editing, generate PDFs.
     */
    public function finalizePayslips(PayrollRun $run, int $userId): array
    {
        $companyId = (int) $run->company_id;

        $payslips = Payslip::where('payroll_run_id', $run->id)
            ->where('status', 'draft')
            ->with('employee')
            ->get();

        $company = Company::find($companyId);
        $finalized = 0;

        foreach ($payslips as $payslip) {
            $payslip->update([
                'status'        => 'finalized',
                'finalized_at'  => now(),
            ]);

            $this->audit($payslip, $companyId, $payslip->employee_id, $userId, 'finalized', [
                'payslip_number' => $payslip->payslip_number,
            ]);

            $finalized++;
        }

        return ['finalized' => $finalized];
    }

    /**
     * Send a single payslip via email.
     */
    public function sendPayslip(Payslip $payslip, int $userId): PayslipDistribution
    {
        $companyId = (int) $payslip->company_id;
        $employee = $payslip->employee;
        $settings = EmployeePayslipSetting::where('company_id', $companyId)
            ->where('employee_id', $payslip->employee_id)
            ->first();

        $email = $settings?->custom_email ?? $employee?->email;

        if (!$email) {
            throw new \RuntimeException('No email address available for this employee.');
        }

        $distribution = PayslipDistribution::create([
            'company_id'    => $companyId,
            'payslip_id'    => $payslip->id,
            'employee_id'   => $payslip->employee_id,
            'channel'       => 'email',
            'status'        => 'pending',
            'email_address' => $email,
        ]);

        try {
            Mail::to($email)->queue(new PayslipMail($payslip, $employee));

            $distribution->update([
                'status'   => 'sent',
                'sent_at'  => now(),
            ]);

            $payslip->update(['status' => 'sent']);

            $this->audit($payslip, $companyId, $payslip->employee_id, $userId, 'sent', [
                'payslip_number'    => $payslip->payslip_number,
                'email_address'     => $email,
                'distribution_id'   => $distribution->id,
            ]);
        } catch (\Throwable $e) {
            $distribution->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->audit($payslip, $companyId, $payslip->employee_id, $userId, 'send_failed', [
                'payslip_number'  => $payslip->payslip_number,
                'error_message'   => $e->getMessage(),
                'distribution_id' => $distribution->id,
            ]);
        }

        return $distribution;
    }

    /**
     * Bulk send all finalized payslips for a run.
     */
    public function bulkSend(PayrollRun $run, int $userId): array
    {
        $companyId = (int) $run->company_id;

        $payslips = Payslip::where('payroll_run_id', $run->id)
            ->whereIn('status', ['finalized', 'sent'])
            ->with('employee')
            ->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($payslips as $payslip) {
            $settings = EmployeePayslipSetting::where('company_id', $companyId)
                ->where('employee_id', $payslip->employee_id)
                ->first();

            if ($settings && !$settings->email_delivery) {
                $skipped++;
                continue;
            }

            $email = $settings?->custom_email ?? $payslip->employee?->email;

            if (!$email) {
                $failed++;
                continue;
            }

            $distribution = PayslipDistribution::where('payslip_id', $payslip->id)
                ->where('channel', 'email')
                ->where('status', 'sent')
                ->first();

            if ($distribution) {
                $skipped++;
                continue;
            }

            try {
                $distribution = PayslipDistribution::create([
                    'company_id'    => $companyId,
                    'payslip_id'    => $payslip->id,
                    'employee_id'   => $payslip->employee_id,
                    'channel'       => 'email',
                    'status'        => 'pending',
                    'email_address' => $email,
                ]);

                Mail::to($email)->queue(new PayslipMail($payslip, $payslip->employee));

                $distribution->update([
                    'status'   => 'sent',
                    'sent_at'  => now(),
                ]);

                $payslip->update(['status' => 'sent']);

                $this->audit($payslip, $companyId, $payslip->employee_id, $userId, 'bulk_sent', [
                    'payslip_number'    => $payslip->payslip_number,
                    'email_address'     => $email,
                    'distribution_id'   => $distribution->id,
                ]);

                $sent++;
            } catch (\Throwable $e) {
                if (isset($distribution)) {
                    $distribution->update([
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }

                $this->audit($payslip, $companyId, $payslip->employee_id, $userId, 'bulk_send_failed', [
                    'payslip_number' => $payslip->payslip_number,
                    'error_message'  => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }

    /**
     * Resend a failed distribution.
     */
    public function resendDistribution(PayslipDistribution $distribution, int $userId): PayslipDistribution
    {
        $payslip = $distribution->payslip;
        $companyId = (int) $distribution->company_id;
        $employee = $payslip->employee;
        $email = $distribution->email_address;

        $distribution->update([
            'status'         => 'pending',
            'retry_count'    => $distribution->retry_count + 1,
            'last_retry_at'  => now(),
            'error_message'  => null,
        ]);

        try {
            Mail::to($email)->queue(new PayslipMail($payslip, $employee));

            $distribution->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

            $payslip->update(['status' => 'sent']);

            $this->audit($payslip, $companyId, $payslip->employee_id, $userId, 'resent', [
                'payslip_number'    => $payslip->payslip_number,
                'email_address'     => $email,
                'distribution_id'   => $distribution->id,
                'retry_count'       => $distribution->retry_count,
            ]);
        } catch (\Throwable $e) {
            $distribution->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $distribution->fresh();
    }

    /**
     * Record a portal view of a payslip.
     */
    public function recordPortalView(Payslip $payslip, int $employeeId, string $ipAddress, string $userAgent): void
    {
        $payslip->update(['status' => 'viewed']);

        PayslipDistribution::create([
            'company_id'    => $payslip->company_id,
            'payslip_id'    => $payslip->id,
            'employee_id'   => $employeeId,
            'channel'       => 'portal',
            'status'        => 'delivered',
            'delivered_at'  => now(),
        ]);

        $this->audit($payslip, $payslip->company_id, $employeeId, null, 'portal_viewed', [
            'payslip_number' => $payslip->payslip_number,
        ], $ipAddress, $userAgent);
    }

    /**
     * Get distribution status summary for a run.
     */
    public function getDistributionStatus(int $companyId, int $runId): array
    {
        $payslips = Payslip::where('payroll_run_id', $runId)
            ->where('company_id', $companyId)
            ->get();

        $total = $payslips->count();
        $sent = $payslips->where('status', 'sent')->count();
        $viewed = $payslips->where('status', 'viewed')->count();
        $finalized = $payslips->where('status', 'finalized')->count();
        $draft = $payslips->where('status', 'draft')->count();

        $failedDistributions = PayslipDistribution::where('company_id', $companyId)
            ->whereHas('payslip', fn($q) => $q->where('payroll_run_id', $runId))
            ->where('status', 'failed')
            ->count();

        return [
            'total'             => $total,
            'sent'              => $sent,
            'viewed'            => $viewed,
            'finalized'         => $finalized,
            'draft'             => $draft,
            'failed'            => $failedDistributions,
            'pending_delivery'  => $sent - $viewed,
        ];
    }

    /**
     * Get audit trail for a payslip.
     */
    public function getAuditTrail(int $companyId, ?int $payslipId = null, ?int $runId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = PayslipAuditLog::where('company_id', $companyId)
            ->with('employee', 'user');

        if ($payslipId) {
            $query->where('payslip_id', $payslipId);
        }

        if ($runId) {
            $query->whereHas('payslip', fn($q) => $q->where('payroll_run_id', $runId));
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Get all payslips for a run with distribution info.
     */
    public function getPayslipsForRun(int $companyId, int $runId): \Illuminate\Database\Eloquent\Collection
    {
        return Payslip::where('company_id', $companyId)
            ->where('payroll_run_id', $runId)
            ->with(['employee', 'distributions'])
            ->orderBy('employee_id')
            ->get();
    }

    /**
     * Employee portal — get payslips visible to an employee.
     */
    public function getEmployeePayslips(int $companyId, int $employeeId): \Illuminate\Database\Eloquent\Collection
    {
        return Payslip::where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['finalized', 'sent', 'viewed'])
            ->with('payrollRun')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Validate employee portal access via payslip_password.
     */
    public function validatePortalAccess(Employee $employee, string $password): bool
    {
        $stored = $employee->payslip_password_decrypted;

        if (!$stored) {
            return false;
        }

        return hash_equals($stored, $password);
    }

    // ── Private helpers ──────────────────────────────────

    private function generatePayslipNumber(int $companyId): string
    {
        $latest = Payslip::where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('payslip_number');

        if ($latest && preg_match('/PS-(\d+)$/', $latest, $m)) {
            return 'PS-' . str_pad(((int) $m[1]) + 1, 6, '0', STR_PAD_LEFT);
        }

        return 'PS-' . str_pad(1, 6, '0', STR_PAD_LEFT);
    }

    private function buildEarnings(PayrollRunItem $item): array
    {
        $data = [
            ['item' => 'Basic Pay', 'basis' => 'Monthly', 'amount' => $item->basic_pay],
        ];

        if ($item->total_allowances > 0) {
            $data[] = ['item' => 'Allowances', 'basis' => 'Fixed', 'amount' => $item->total_allowances];
        }

        return $data;
    }

    private function buildDeductions(PayrollRunItem $item): array
    {
        $data = [];

        if ($item->paye > 0) {
            $data[] = ['item' => 'PAYE', 'basis' => 'Tax table', 'amount' => $item->paye];
        }

        if ($item->pension_ee > 0) {
            $data[] = ['item' => 'Pension (EE)', 'basis' => 'Employee %', 'amount' => $item->pension_ee];
        }

        if ($item->total_deductions > ($item->paye + $item->pension_ee)) {
            $other = $item->total_deductions - $item->paye - $item->pension_ee;
            $data[] = ['item' => 'Other Deductions', 'basis' => 'Fixed', 'amount' => $other];
        }

        return $data;
    }

    private function buildEmployerContributions(PayrollRunItem $item): array
    {
        $data = [];

        if ($item->pension_er > 0) {
            $data[] = ['item' => 'Pension (ER)', 'amount' => $item->pension_er];
        }

        return $data;
    }

    private function computeYtd(int $companyId, int $employeeId, float $currentGross, float $currentPaye, float $currentPension): array
    {
        $ytd = PayrollRunItem::whereHas('payrollRun', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('status', 'posted');
        })
            ->where('employee_id', $employeeId)
            ->selectRaw('SUM(gross_pay) as ytd_gross, SUM(paye) as ytd_paye, SUM(pension_ee) as ytd_pension')
            ->first();

        return [
            'gross'   => ($ytd?->ytd_gross ?? 0) + $currentGross,
            'paye'    => ($ytd?->ytd_paye ?? 0) + $currentPaye,
            'pension' => ($ytd?->ytd_pension ?? 0) + $currentPension,
        ];
    }

    private function audit(Payslip $payslip, int $companyId, int $employeeId, ?int $userId, string $action, array $metadata = [], ?string $ipAddress = null, ?string $userAgent = null): void
    {
        PayslipAuditLog::create([
            'company_id'   => $companyId,
            'payslip_id'   => $payslip->id,
            'employee_id'  => $employeeId,
            'user_id'      => $userId,
            'action'       => $action,
            'metadata'     => $metadata,
            'ip_address'   => $ipAddress,
            'user_agent'   => $userAgent,
            'created_at'   => now(),
        ]);
    }
}
