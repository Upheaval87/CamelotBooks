<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\QuotationStatusService;
use Illuminate\Http\Request;
class QuotationStatusController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $data = app(QuotationStatusService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.quotation-status', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
