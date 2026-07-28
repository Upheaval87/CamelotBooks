<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\TaxDepreciationScheduleService;
use Illuminate\Http\Request;

class TaxDepreciationScheduleController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(TaxDepreciationScheduleService::class)->generate($companyId);
        return view('accounting.reports.tax-depreciation-schedule', $data);
    }
}
