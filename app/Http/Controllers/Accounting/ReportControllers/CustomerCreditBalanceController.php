<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\CustomerCreditBalanceService;
use Illuminate\Http\Request;
class CustomerCreditBalanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(CustomerCreditBalanceService::class)->generate($companyId);
        return view('accounting.reports.customer-credit-balance', $data);
    }
}
