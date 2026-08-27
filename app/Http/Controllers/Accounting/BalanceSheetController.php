<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reporting\BalanceSheetService;
use App\Services\Reporting\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class BalanceSheetController extends Controller
{
    public function __construct(private BalanceSheetService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $asOfDate);

        $prevAsOf = \Carbon\Carbon::parse($asOfDate)->subYear()->toDateString();
        $prevStatement = $this->service->generate($companyId, $branchId, $prevAsOf);

        $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'asOfDate'),
        );

        return view('accounting.balance-sheet.index', array_merge($statement, compact('branches', 'branchId', 'asOfDate', 'prevStatement', 'prevAsOf', 'currencySymbol')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $asOfDate);

        if (!$statement['balanced']) {
            return redirect()->route('accounting.balance-sheet.index', $request->query())
                ->withErrors(['export' => 'Export blocked: balance sheet is not balanced. Assets must equal Liabilities + Equity.']);
        }

        $filename = "balance_sheet_{$asOfDate}.csv";

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'asOfDate'),
            outputFormat: 'csv',
        );

        return Response::streamDownload(function () use ($statement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Balance Sheet', '', 'Amount']);
            fputcsv($handle, ['As of', $statement['as_of_date'], '']);
            fputcsv($handle, ['', '', '']);

            foreach ($statement['groups']['asset'] as $subType => $items) {
                if (empty($items)) continue;
                fputcsv($handle, ['Assets - ' . ucwords(str_replace('_', ' ', $subType)), '', '']);
                foreach ($items as $item) {
                    fputcsv($handle, ['  ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format($item['balance'], 2, '.', '')]);
                }
            }
            fputcsv($handle, ['Total Assets', '', number_format($statement['total_assets'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            foreach ($statement['groups']['liability'] as $subType => $items) {
                if (empty($items)) continue;
                fputcsv($handle, ['Liabilities - ' . ucwords(str_replace('_', ' ', $subType)), '', '']);
                foreach ($items as $item) {
                    fputcsv($handle, ['  ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format($item['balance'], 2, '.', '')]);
                }
            }
            fputcsv($handle, ['Total Liabilities', '', number_format($statement['total_liabilities'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Equity', '', '']);
            foreach ($statement['groups']['equity'] as $subType => $items) {
                foreach ($items as $item) {
                    fputcsv($handle, ['  ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format($item['balance'], 2, '.', '')]);
                }
            }
            fputcsv($handle, ['  Current Year Earnings', '', number_format($statement['current_year_earnings'], 2, '.', '')]);
            fputcsv($handle, ['Total Equity', '', number_format($statement['total_equity'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Total Liabilities & Equity', '', number_format($statement['total_equity'] + $statement['total_liabilities'], 2, '.', '')]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    public function exportPdf(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $asOfDate);

        if (!$statement['balanced']) {
            return redirect()->route('accounting.balance-sheet.index', $request->query())
                ->withErrors(['export' => 'Export blocked: balance sheet is not balanced. Assets must equal Liabilities + Equity.']);
        }

        $company = Company::findOrFail($companyId);

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'asOfDate'),
            outputFormat: 'pdf',
        );

        $content = view('accounting.balance-sheet.print', array_merge($statement, compact('company', 'asOfDate')))->render();

        return response()->view('accounting.print-export', [
            'title' => "Balance Sheet as of {$asOfDate}",
            'content' => $content,
        ])->header('Content-Type', 'text/html');
    }
}
