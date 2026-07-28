<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\EisSubmissionStatusService;
use Illuminate\Http\Request;

class EisSubmissionStatusController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(EisSubmissionStatusService::class)->generate($companyId);
        return view('accounting.reports.eis-submission-status', $data);
    }
}
