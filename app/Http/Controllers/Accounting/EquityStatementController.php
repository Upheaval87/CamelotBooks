<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Accounting\Concerns\StatementPdfMeta;
use App\Models\Company;
use App\Services\Reporting\EquityStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EquityStatementController extends Controller
{
    use StatementPdfMeta;

    public function __construct(private EquityStatementService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $dateFrom, $dateTo, $branchId);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.equity-statement.index', array_merge($statement, compact('branches', 'branchId', 'dateFrom', 'dateTo')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $dateFrom, $dateTo, $branchId);

        $filename = "equity_statement_{$dateFrom}_to_{$dateTo}.csv";

        return Response::streamDownload(function () use ($statement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Statement of Changes in Equity']);
            fputcsv($handle, ['Period', "{$statement['date_from']} to {$statement['date_to']}"]);
            fputcsv($handle, ['', '', '', '']);
            fputcsv($handle, ['Account', 'Opening Balance', 'Movement', 'Closing Balance']);

            foreach ($statement['movements'] as $item) {
                fputcsv($handle, [
                    $item['account']->code . ' - ' . $item['account']->name,
                    number_format($item['opening'], 2, '.', ''),
                    number_format($item['movement'], 2, '.', ''),
                    number_format($item['closing'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, ['', '', '', '']);
            fputcsv($handle, [
                'Net Income for Period',
                '',
                number_format($statement['net_income'], 2, '.', ''),
                '',
            ]);
            fputcsv($handle, [
                'Total Equity',
                number_format($statement['total_opening'], 2, '.', ''),
                '',
                number_format($statement['total_closing'], 2, '.', ''),
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    public function exportPdf(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $dateFrom, $dateTo, $branchId);
        $company = Company::findOrFail($companyId);

        $periodLabel = 'For the period '
            . \Carbon\Carbon::parse($dateFrom)->format('d M Y')
            . ' — ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');

        $title = 'Statement of Changes in Equity';
        $meta = $this->statementPdfMeta($company, $branchId, $this->statementPreparedBy(), $title, $periodLabel);

        $content = view('accounting.equity-statement.print', array_merge($statement, compact('company', 'dateFrom', 'dateTo')))->render();

        return response()->view('accounting.print-export', [
            'title'   => "Statement of Changes in Equity — {$dateFrom} to {$dateTo}",
            'content' => $content,
            'meta'    => $meta,
        ])->header('Content-Type', 'text/html');
    }
}
