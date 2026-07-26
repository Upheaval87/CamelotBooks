<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reporting\IncomeStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class IncomeStatementController extends Controller
{
    public function __construct(private IncomeStatementService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $compareMode = $request->input('compare_mode');

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo, $compareMode, $costCenterId);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = \App\Models\CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.income-statement.index', array_merge($statement, compact('branches', 'branchId', 'costCenters', 'costCenterId', 'dateFrom', 'dateTo', 'compareMode')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo, null, $costCenterId);

        $filename = "income_statement_{$dateFrom}_to_{$dateTo}.csv";

        return Response::streamDownload(function () use ($statement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Income Statement', '', 'Amount']);
            fputcsv($handle, ['Period', $statement['date_from'] . ' to ' . $statement['date_to'], '']);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Income', '', '']);
            foreach ($statement['groups']['income'] as $subType => $items) {
                fputcsv($handle, ['  ' . ucwords(str_replace('_', ' ', $subType)), '', '']);
                foreach ($items as $item) {
                    fputcsv($handle, ['    ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format($item['net'], 2, '.', '')]);
                }
            }
            fputcsv($handle, ['Total Income', '', number_format($statement['total_income'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Expenses', '', '']);
            foreach ($statement['groups']['expense'] as $subType => $items) {
                fputcsv($handle, ['  ' . ucwords(str_replace('_', ' ', $subType)), '', '']);
                foreach ($items as $item) {
                    fputcsv($handle, ['    ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format($item['net'], 2, '.', '')]);
                }
            }
            fputcsv($handle, ['Total Expenses', '', number_format($statement['total_expenses'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            $label = $statement['net_income'] >= 0 ? 'Net Income' : 'Net Loss';
            fputcsv($handle, [$label, '', number_format(abs($statement['net_income']), 2, '.', '')]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    public function exportPdf(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo, null, $costCenterId);
        $company = Company::findOrFail($companyId);

        $content = view('accounting.income-statement.print', array_merge($statement, compact('company', 'dateFrom', 'dateTo')))->render();

        return response()->view('accounting.print-export', [
            'title' => "Income Statement {$dateFrom} to {$dateTo}",
            'content' => $content,
        ])->header('Content-Type', 'text/html');
    }
}
