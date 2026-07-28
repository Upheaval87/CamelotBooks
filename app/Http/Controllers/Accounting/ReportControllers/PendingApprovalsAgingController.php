<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PendingApprovalsAgingService;
use Illuminate\Http\Request;

class PendingApprovalsAgingController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(PendingApprovalsAgingService::class)->generate($companyId);
        return view('accounting.reports.pending-approvals-aging', $data);
    }
}
