<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\StockCountVarianceService;
use Illuminate\Http\Request;
class StockCountVarianceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $stockCountId = $request->input('stock_count_id') ? (int)$request->input('stock_count_id') : null;
        $data = app(StockCountVarianceService::class)->generate($companyId, $stockCountId);
        return view('accounting.reports.stock-count-variance', array_merge($data, compact('stockCountId')));
    }
}
