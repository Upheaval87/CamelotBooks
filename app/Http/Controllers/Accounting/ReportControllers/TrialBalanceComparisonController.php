<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\TrialBalanceComparisonService;
use Illuminate\Http\Request;
class TrialBalanceComparisonController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom1 = $request->input('date_from1', now()->startOfYear()->format('Y-m-d'));
        $dateTo1 = $request->input('date_to1', now()->format('Y-m-d'));
        $dateFrom2 = $request->input('date_from2', now()->subYear()->startOfYear()->format('Y-m-d'));
        $dateTo2 = $request->input('date_to2', now()->subYear()->format('Y-m-d'));
        $data = app(TrialBalanceComparisonService::class)->generate($companyId, $dateFrom1, $dateTo1, $dateFrom2, $dateTo2);
        return view('accounting.reports.trial-balance-comparison', array_merge($data, compact('dateFrom1', 'dateTo1', 'dateFrom2', 'dateTo2')));
    }
}
