<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\SalesByItemService;
use Illuminate\Http\Request;
class SalesByItemController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $data = app(SalesByItemService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.sales-by-item', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
