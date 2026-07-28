<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\StockMovementService;
use Illuminate\Http\Request;
class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $productId = $request->input('product_id') ? (int)$request->input('product_id') : null;
        $branchId = $request->input('branch_id') ? (int)$request->input('branch_id') : null;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $data = app(StockMovementService::class)->generate($companyId, $productId, $branchId, $dateFrom, $dateTo);
        return view('accounting.reports.stock-movement', array_merge($data, compact('productId', 'branchId', 'dateFrom', 'dateTo')));
    }
}
