<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\VendorCreditBalanceService;
use Illuminate\Http\Request;
class VendorCreditBalanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(VendorCreditBalanceService::class)->generate($companyId);
        return view('accounting.reports.vendor-credit-balance', $data);
    }
}
