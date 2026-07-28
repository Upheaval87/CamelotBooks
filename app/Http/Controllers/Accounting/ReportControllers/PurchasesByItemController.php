<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PurchasesByItemService;
use Illuminate\Http\Request;
class PurchasesByItemController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $data = app(PurchasesByItemService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.purchases-by-item', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
