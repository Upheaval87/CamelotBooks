<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ChartOfAccountsService;
use Illuminate\Http\Request;

class ChartOfAccountsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $type = $request->input('type');
        $data = app(ChartOfAccountsService::class)->generate($companyId, $type);
        return view('accounting.reports.chart-of-accounts', $data);
    }
}
