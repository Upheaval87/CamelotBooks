<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PayrollSummaryService;
use Illuminate\Http\Request;
class PayrollSummaryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $data = app(PayrollSummaryService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.payroll-summary', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
