<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PayrollRegisterService;
use Illuminate\Http\Request;
class PayrollRegisterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $payrollRunId = $request->input('payroll_run_id') ? (int)$request->input('payroll_run_id') : null;
        $data = app(PayrollRegisterService::class)->generate($companyId, $payrollRunId);
        return view('accounting.reports.payroll-register', array_merge($data, compact('payrollRunId')));
    }
}
