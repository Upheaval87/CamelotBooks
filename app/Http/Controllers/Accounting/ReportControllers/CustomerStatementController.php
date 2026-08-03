<?php
namespace App\Http\Controllers\Accounting\ReportControllers;
use App\Http\Controllers\Controller;
use App\Services\Reporting\CustomerStatementService;
use Illuminate\Http\Request;

class CustomerStatementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $customerId = $request->input('customer_id');
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));

        $customer = $customerId
            ? \App\Models\Customer::where('company_id', $companyId)->find((int) $customerId)
            : null;

        if ($customer) {
            $data = app(CustomerStatementService::class)->generate($companyId, $customer->id, $dateFrom, $dateTo);
        } else {
            $data = ['transactions' => [], 'opening_balance' => 0, 'closing_balance' => 0, 'total_debit' => 0, 'total_credit' => 0];
        }

        return view('accounting.reports.customer-statement', array_merge($data, compact('dateFrom', 'dateTo', 'customerId', 'customer')));
    }
}
