<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\BankBalancesService;
use Illuminate\Http\Request;
class BankBalancesController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(BankBalancesService::class)->generate($companyId);
        return view('accounting.reports.bank-balances', $data);
    }
}
