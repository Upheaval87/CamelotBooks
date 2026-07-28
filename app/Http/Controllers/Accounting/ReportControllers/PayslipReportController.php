<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PayslipReportService;
use Illuminate\Http\Request;

class PayslipReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $payrollRunId = $request->input('payroll_run_id');
        $data = app(PayslipReportService::class)->generate($companyId, $payrollRunId);
        return view('accounting.reports.payslip-report', array_merge($data, compact('payrollRunId')));
    }
}
