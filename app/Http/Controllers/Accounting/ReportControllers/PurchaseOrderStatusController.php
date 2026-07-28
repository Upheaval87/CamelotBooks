<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PurchaseOrderStatusService;
use Illuminate\Http\Request;
class PurchaseOrderStatusController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(PurchaseOrderStatusService::class)->generate($companyId);
        return view('accounting.reports.po-status', $data);
    }
}
