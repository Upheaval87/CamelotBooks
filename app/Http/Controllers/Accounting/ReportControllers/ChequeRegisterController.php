<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ChequeRegisterService;
use Illuminate\Http\Request;

class ChequeRegisterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $data = app(ChequeRegisterService::class)->generate($companyId, $dateFrom, $dateTo);
        return view('accounting.reports.cheque-register', array_merge($data, compact('dateFrom', 'dateTo')));
    }
}
