<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\BankReconciliationHistoryService;
use Illuminate\Http\Request;

class BankReconciliationHistoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $bankAccountId = $request->input('bank_account_id');
        $data = app(BankReconciliationHistoryService::class)->generate($companyId, $bankAccountId);
        return view('accounting.reports.bank-reconciliation-history', array_merge($data, compact('bankAccountId')));
    }
}
