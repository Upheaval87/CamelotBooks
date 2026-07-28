<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\AssetDisposalReportService;
use Illuminate\Http\Request;

class AssetDisposalReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $data = app(AssetDisposalReportService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.asset-disposal-report', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
