<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ConsolidatedIncomeStatementService;
use Illuminate\Http\Request;

class ConsolidatedIncomeStatementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $companyIds = $request->input('company_ids', []);
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $data = app(ConsolidatedIncomeStatementService::class)->generate($companyId, $companyIds, $dateFrom, $dateTo);
        return view('accounting.reports.consolidated-income-statement', array_merge($data, compact('companyIds', 'dateFrom', 'dateTo')));
    }
}
