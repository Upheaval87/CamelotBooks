<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PayeRemittanceReportService;
use Illuminate\Http\Request;

class PayeRemittanceReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(PayeRemittanceReportService::class)->generate($companyId);
        return view('accounting.reports.paye-remittance-report', $data);
    }
}
