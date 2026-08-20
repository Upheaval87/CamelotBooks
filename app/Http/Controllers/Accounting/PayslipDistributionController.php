<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePayslipSetting;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayslipAuditLog;
use App\Models\PayslipDistribution;
use App\Services\Payroll\PayslipDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Validation\Rule;

class PayslipDistributionController extends Controller
{
    public function __construct(
        private PayslipDistributionService $service,
    ) {}

    /**
     * Pay Run Finalized — CTA page after a run is marked approved/posted.
     * Shows which employees have payslips ready to distribute.
     */
    public function payRunFinalized(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $run->company_id === $companyId, 404);

        $run->load('items.employee');

        $payslips = Payslip::where('payroll_run_id', $run->id)
            ->where('company_id', $companyId)
            ->with('employee')
            ->get();

        $status = $this->service->getDistributionStatus($companyId, $run->id);

        return view('accounting.payroll.distribution-finalized', compact(
            'run',
            'payslips',
            'status',
        ));
    }

    /**
     * Pre-Distribution Validation — check all employees have emails,
     * passwords set, etc. before bulk send.
     */
    public function validateBeforeSend(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $run->company_id === $companyId, 404);

        $payslips = Payslip::where('payroll_run_id', $run->id)
            ->where('company_id', $companyId)
            ->where('status', 'finalized')
            ->with('employee')
            ->get();

        $issues = [];
        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $empIssues = [];

            if (empty($employee?->email)) {
                $empIssues[] = 'No email address on file.';
            }

            $settings = EmployeePayslipSetting::where('company_id', $companyId)
                ->where('employee_id', $payslip->employee_id)
                ->first();

            if ($settings && !$settings->email_delivery) {
                $empIssues[] = 'Email delivery disabled in employee settings.';
            }

            if ($empIssues) {
                $issues[] = [
                    'payslip'   => $payslip,
                    'employee'  => $employee,
                    'issues'    => $empIssues,
                ];
            }
        }

        $allValid = empty($issues);

        return view('accounting.payroll.distribution-validate', compact(
            'run',
            'payslips',
            'issues',
            'allValid',
        ));
    }

    /**
     * Bulk send — dispatch email to all finalized payslips for a run.
     */
    public function bulkSend(PayrollRun $run, Request $request)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $run->company_id === $companyId, 404);

        $result = $this->service->bulkSend($run, auth()->id());

        return redirect()->route('accounting.payroll.distribution.status', $run)
            ->with('success', "Sent: {$result['sent']}, Failed: {$result['failed']}, Skipped: {$result['skipped']}.");
    }

    /**
     * Distribution Status — shows send progress for each employee.
     */
    public function status(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $run->company_id === $companyId, 404);

        $run->load('items.employee');

        $payslips = $this->service->getPayslipsForRun($companyId, $run->id);
        $status = $this->service->getDistributionStatus($companyId, $run->id);

        return view('accounting.payroll.distribution-status', compact(
            'run',
            'payslips',
            'status',
        ));
    }

    /**
     * Send a single payslip.
     */
    public function send(Payslip $payslip)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $payslip->company_id === $companyId, 404);

        $this->service->sendPayslip($payslip, auth()->id());

        return back()->with('success', 'Payslip sent.');
    }

    /**
     * Resend a failed distribution.
     */
    public function resend(PayslipDistribution $distribution)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $distribution->company_id === $companyId, 404);

        $this->service->resendDistribution($distribution, auth()->id());

        return back()->with('success', 'Payslip resent.');
    }

    /**
     * Employee Profile Settings — manage payslip delivery preferences.
     */
    public function employeeSettings()
    {
        $companyId = (int) session('current_company_id');

        $employees = Employee::forCompany($companyId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $settings = EmployeePayslipSetting::where('company_id', $companyId)
            ->get()
            ->keyBy('employee_id');

        return view('accounting.payroll.distribution-employee-settings', compact(
            'employees',
            'settings',
        ));
    }

    /**
     * Update an employee's payslip settings.
     */
    public function updateEmployeeSettings(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'email_delivery' => 'required|boolean',
            'portal_access'  => 'required|boolean',
            'custom_email'   => 'nullable|email|max:255',
        ]);

        EmployeePayslipSetting::updateOrCreate(
            [
                'company_id'  => $companyId,
                'employee_id' => $validated['employee_id'],
            ],
            [
                'email_delivery' => $validated['email_delivery'],
                'portal_access'  => $validated['portal_access'],
                'custom_email'   => $validated['custom_email'] ?? null,
            ]
        );

        return back()->with('success', 'Employee payslip settings updated.');
    }

    /**
     * Audit Trail — chronological log of all payslip distribution events.
     */
    public function auditTrail(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $query = PayslipAuditLog::where('company_id', $companyId)
            ->with('employee', 'user', 'payslip');

        if ($request->filled('run_id')) {
            $query->whereHas('payslip', fn($q) => $q->where('payroll_run_id', $request->run_id));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $runs = PayrollRun::forCompany($companyId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $employees = Employee::forCompany($companyId)
            ->active()
            ->orderBy('first_name')
            ->get();

        return view('accounting.payroll.distribution-audit', compact(
            'logs',
            'runs',
            'employees',
        ));
    }

    /**
     * Export audit trail as CSV.
     */
    public function exportAuditCsv(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $query = PayslipAuditLog::where('company_id', $companyId)
            ->with('employee', 'user', 'payslip');

        if ($request->filled('run_id')) {
            $query->whereHas('payslip', fn($q) => $q->where('payroll_run_id', $request->run_id));
        }

        $logs = $query->orderByDesc('created_at')->get();

        $filename = 'payslip-audit-trail-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Time', 'Employee', 'Payslip #', 'Action', 'User', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d'),
                    $log->created_at?->format('H:i:s'),
                    $log->employee?->full_name ?? '—',
                    $log->payslip?->payslip_number ?? '—',
                    $log->action,
                    $log->user?->name ?? 'System',
                    $log->ip_address ?? '—',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Finalize all draft payslips for a run.
     */
    public function finalize(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $run->company_id === $companyId, 404);

        $this->service->finalizePayslips($run, auth()->id());

        return back()->with('success', 'Payslips finalized. Ready for distribution.');
    }

    /**
     * Generate payslips for a run that doesn't have them yet.
     */
    public function generate(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');
        abort_unless((int) $run->company_id === $companyId, 404);

        $result = $this->service->generatePayslips($run, auth()->id());

        return back()->with('success', "Generated: {$result['generated']}, Skipped: {$result['skipped']}.");
    }
}
