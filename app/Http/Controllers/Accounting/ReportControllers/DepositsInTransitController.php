<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\DepositsInTransitService;
use Illuminate\Http\Request;
class DepositsInTransitController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $data = app(DepositsInTransitService::class)->generate($companyId);
        return view('accounting.reports.deposits-in-transit', $data);
    }
}
