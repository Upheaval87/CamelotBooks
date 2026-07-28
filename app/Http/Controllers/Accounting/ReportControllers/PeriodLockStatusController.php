<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PeriodLockStatusService;
use Illuminate\Http\Request;
class PeriodLockStatusController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(PeriodLockStatusService::class)->generate($companyId);
        return view('accounting.reports.period-lock-status', $data);
    }
}
