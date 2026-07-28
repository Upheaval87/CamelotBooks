<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\VendorStatementService;
use Illuminate\Http\Request;

class VendorStatementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $vendorId = $request->input('vendor_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $data = app(VendorStatementService::class)->generate($companyId, $dateFrom, $dateTo, $vendorId);
        return view('accounting.reports.vendor-statement', array_merge($data, compact('dateFrom', 'dateTo', 'vendorId')));
    }
}
