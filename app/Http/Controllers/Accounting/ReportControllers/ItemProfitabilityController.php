<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ItemProfitabilityService;
use Illuminate\Http\Request;
class ItemProfitabilityController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $data = app(ItemProfitabilityService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.item-profitability', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
