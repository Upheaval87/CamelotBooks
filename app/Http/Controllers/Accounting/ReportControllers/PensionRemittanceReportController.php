<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PensionRemittanceReportService;
use Illuminate\Http\Request;

class PensionRemittanceReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(PensionRemittanceReportService::class)->generate($companyId);
        return view('accounting.reports.pension-remittance-report', $data);
    }
}
