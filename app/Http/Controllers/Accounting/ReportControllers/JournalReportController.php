<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\JournalReportService;
use Illuminate\Http\Request;
class JournalReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $data = app(JournalReportService::class)->generate($companyId, $dateFrom, $dateTo, $branchId ? (int)$branchId : null);
        return view('accounting.reports.journal-report', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
