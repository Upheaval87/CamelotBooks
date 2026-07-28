<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ConsolidatedBalanceSheetService;
use Illuminate\Http\Request;

class ConsolidatedBalanceSheetController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $companyIds = $request->input('company_ids', []);
        $asOfDate = $request->input('as_of_date');
        $data = app(ConsolidatedBalanceSheetService::class)->generate($companyId, $companyIds, $asOfDate);
        return view('accounting.reports.consolidated-balance-sheet', array_merge($data, compact('companyIds', 'asOfDate')));
    }
}
