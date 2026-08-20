<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;
use App\Services\Payroll\PayslipDistributionService;
use Illuminate\Http\Request;

class EmployeePayslipPortalController extends Controller
{
    public function __construct(
        private PayslipDistributionService $service,
    ) {}

    /**
     * Portal login — employees enter their employee number + payslip password.
     */
    public function showLogin()
    {
        return view('accounting.payroll.portal-login');
    }

    /**
     * Portal authenticate.
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string',
            'password'        => 'required|string',
        ]);

        $companyId = (int) session('current_company_id');

        $employee = Employee::where('company_id', $companyId)
            ->where('employee_number', $request->employee_number)
            ->active()
            ->first();

        if (!$employee || !$this->service->validatePortalAccess($employee, $request->password)) {
            return back()->withErrors([
                'employee_number' => 'Invalid employee number or password.',
            ])->onlyInput('employee_number');
        }

        session(['portal_employee_id' => $employee->id]);
        session(['portal_company_id' => $companyId]);

        return redirect()->route('accounting.payroll.portal.index');
    }

    /**
     * Portal — list payslips for the authenticated employee.
     */
    public function index()
    {
        $employeeId = session('portal_employee_id');
        $companyId = (int) session('portal_company_id');

        abort_unless($employeeId && $companyId, 403);

        $employee = Employee::where('id', $employeeId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $payslips = $this->service->getEmployeePayslips($companyId, $employeeId);

        return view('accounting.payroll.portal-index', compact(
            'employee',
            'payslips',
        ));
    }

    /**
     * Portal — secure preview of a payslip.
     */
    public function preview(Payslip $payslip)
    {
        $employeeId = session('portal_employee_id');
        $companyId = (int) session('portal_company_id');

        abort_unless($employeeId && $companyId, 403);
        abort_unless((int) $payslip->company_id === $companyId, 404);
        abort_unless((int) $payslip->employee_id === $employeeId, 403);
        abort_unless(in_array($payslip->status, ['finalized', 'sent', 'viewed']), 404);

        $payslip->load(['employee', 'payrollRun', 'payrollRun.payeTable', 'payrollRun.pensionScheme']);

        $this->service->recordPortalView($payslip, $employeeId, request()->ip(), request()->userAgent());

        $company = $payslip->company;

        return view('accounting.payroll.portal-preview', compact(
            'payslip',
            'company',
        ));
    }

    /**
     * Portal — logout.
     */
    public function logout()
    {
        session()->forget(['portal_employee_id', 'portal_company_id']);

        return redirect()->route('accounting.payroll.portal.login');
    }
}
