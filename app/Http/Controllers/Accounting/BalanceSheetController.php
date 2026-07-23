<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reporting\BalanceSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

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

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.balance-sheet.index', array_merge($statement, compact('branches', 'branchId', 'asOfDate')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $asOfDate);

        $filename = "balance_sheet_{$asOfDate}.csv";

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
        $company = Company::findOrFail($companyId);

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('CamelotBooks');
        $pdf->SetTitle("Balance Sheet as of {$asOfDate}");
        $pdf->setHeaderFont(['helvetica', '', 8]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Balance Sheet', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "As of: {$asOfDate}", 0, 1, 'L');
        $pdf->Ln(4);

        $colWidths = [130, 50];

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(68, 68, 68);
        $pdf->SetTextColor(255);
        $pdf->Cell($colWidths[0], 7, 'Description', 1, 0, 'L', true);
        $pdf->Cell($colWidths[1], 7, 'Amount', 1, 1, 'R', true);
        $pdf->SetTextColor(0);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Assets', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['groups']['asset'] as $subType => $items) {
            foreach ($items as $item) {
                $pdf->Cell($colWidths[0], 6, '  ' . $item['account']->code . ' - ' . $item['account']->name, 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, number_format($item['balance'], 2), 1, 1, 'R');
            }
        }
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Total Assets', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['total_assets'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Liabilities', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['groups']['liability'] as $subType => $items) {
            foreach ($items as $item) {
                $pdf->Cell($colWidths[0], 6, '  ' . $item['account']->code . ' - ' . $item['account']->name, 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, number_format($item['balance'], 2), 1, 1, 'R');
            }
        }
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Total Liabilities', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['total_liabilities'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Equity', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['groups']['equity'] as $subType => $items) {
            foreach ($items as $item) {
                $pdf->Cell($colWidths[0], 6, '  ' . $item['account']->code . ' - ' . $item['account']->name, 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, number_format($item['balance'], 2), 1, 1, 'R');
            }
        }
        $pdf->Cell($colWidths[0], 6, '  Current Year Earnings', 1, 0, 'L');
        $pdf->Cell($colWidths[1], 6, number_format($statement['current_year_earnings'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Total Equity', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['total_equity'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidths[0], 8, 'Total Liabilities & Equity', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 8, number_format($statement['total_liabilities'] + $statement['total_equity'], 2), 1, 1, 'R');

        $filename = "balance_sheet_{$asOfDate}.pdf";
        $pdf->Output($filename, 'D');
    }
}
