<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Accounting\Concerns\StatementPdfMeta;
use App\Models\Company;
use App\Services\Reporting\CashFlowStatementService;
use App\Services\Reporting\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CashFlowController extends Controller
{
    use StatementPdfMeta;

    public function __construct(private CashFlowStatementService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo);

        $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        $dp = (int) \App\Models\SystemSetting::getValue('currency', 'decimal_places', $companyId, '2');
        $currency = $currencySymbol ? trim($currencySymbol) : '$';

        $company = Company::findOrFail($companyId);
        $preparedBy = $this->statementPreparedBy();

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'dateFrom', 'dateTo'),
        );

        return view('accounting.cash-flow.index', array_merge($statement, compact('branches', 'branchId', 'dateFrom', 'dateTo', 'currencySymbol', 'company', 'dp', 'currency', 'preparedBy')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo);

        $filename = "cash_flow_{$dateFrom}_to_{$dateTo}.csv";

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'dateFrom', 'dateTo'),
            outputFormat: 'csv',
        );

        return Response::streamDownload(function () use ($statement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Cash Flow Statement', '', 'Amount']);
            fputcsv($handle, ['Period', $statement['date_from'] . ' to ' . $statement['date_to'], '']);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Operating Activities', '', '']);
            fputcsv($handle, ['  Net Income', '', number_format($statement['net_income'], 2, '.', '')]);
            foreach ($statement['non_cash_expenses']['items'] as $item) {
                fputcsv($handle, ['  Add: ' . $item['account']->name, '', number_format($item['amount'], 2, '.', '')]);
            }
            foreach ($statement['sections']['operating'] as $item) {
                $label = $item['change'] > 0 ? 'Increase in' : 'Decrease in';
                fputcsv($handle, ['  ' . $label . ' ' . $item['account']->name, '', number_format($item['cash_effect'], 2, '.', '')]);
            }
            fputcsv($handle, ['Net Cash from Operating', '', number_format($statement['operating_total'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Investing Activities', '', '']);
            foreach ($statement['sections']['investing'] as $item) {
                $label = $item['change'] > 0 ? 'Increase in' : 'Decrease in';
                fputcsv($handle, ['  ' . $label . ' ' . $item['account']->name, '', number_format($item['cash_effect'], 2, '.', '')]);
            }
            fputcsv($handle, ['Net Cash from Investing', '', number_format($statement['investing_total'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Financing Activities', '', '']);
            foreach ($statement['sections']['financing'] as $item) {
                $label = $item['change'] > 0 ? 'Increase in' : 'Decrease in';
                fputcsv($handle, ['  ' . $label . ' ' . $item['account']->name, '', number_format($item['cash_effect'], 2, '.', '')]);
            }
            fputcsv($handle, ['Net Cash from Financing', '', number_format($statement['financing_total'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Net Change in Cash', '', number_format($statement['net_change'], 2, '.', '')]);
            fputcsv($handle, ['Beginning Cash Balance', '', number_format($statement['beginning_cash'], 2, '.', '')]);
            fputcsv($handle, ['Ending Cash Balance', '', number_format($statement['ending_cash'], 2, '.', '')]);
            fputcsv($handle, ['Actual Ending Cash', '', number_format($statement['actual_ending_cash'], 2, '.', '')]);

            if ($statement['mismatch'] !== null) {
                fputcsv($handle, ['', '', '']);
                fputcsv($handle, ['WARNING', 'Ending cash does not match actual bank balances.', '']);
                fputcsv($handle, ['Difference', '', number_format(abs($statement['mismatch']), 2, '.', '')]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    public function exportPdf(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo);

        $company = Company::findOrFail($companyId);

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'dateFrom', 'dateTo'),
            outputFormat: 'pdf',
        );

        $periodLabel = 'For the period '
            . \Carbon\Carbon::parse($dateFrom)->format('d M Y')
            . ' — ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');

        $title = 'Cash Flow Statement';
        $meta = $this->statementPdfMeta($company, $branchId, $this->statementPreparedBy(), $title, $periodLabel);

        if ($statement['mismatch'] === null) {
            $meta['check'] = 'Reconciled — Ending Cash equals actual bank balances';
        } else {
            $meta['warn'] = 'Ending cash ('
                . number_format($statement['ending_cash'], 2)
                . ') does not match actual bank balances ('
                . number_format($statement['actual_ending_cash'], 2)
                . '). Difference: '
                . number_format(abs($statement['mismatch']), 2)
                . '.';
        }

        $content = view('accounting.cash-flow.print', array_merge($statement, compact('company', 'dateFrom', 'dateTo')))->render();

        return response()->view('accounting.print-export', [
            'title' => "Cash Flow Statement {$dateFrom} to {$dateTo}",
            'content' => $content,
            'meta'  => $meta,
        ])->header('Content-Type', 'text/html');
    }
}
