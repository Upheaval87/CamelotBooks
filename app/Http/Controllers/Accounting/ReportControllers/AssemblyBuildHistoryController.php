<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\AssemblyBuildHistoryService;
use Illuminate\Http\Request;

class AssemblyBuildHistoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $data = app(AssemblyBuildHistoryService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.assembly-build-history', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
