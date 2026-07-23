<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reporting\IncomeStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class IncomeStatementController extends Controller
{
    public function __construct(private IncomeStatementService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $compareMode = $request->input('compare_mode');

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo, $compareMode);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.income-statement.index', array_merge($statement, compact('branches', 'branchId', 'dateFrom', 'dateTo', 'compareMode')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo);

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
                    fputcsv($handle, ['    ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format(max(0, $item['net']), 2, '.', '')]);
                }
            }
            fputcsv($handle, ['Total Income', '', number_format($statement['total_income'], 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Expenses', '', '']);
            foreach ($statement['groups']['expense'] as $subType => $items) {
                fputcsv($handle, ['  ' . ucwords(str_replace('_', ' ', $subType)), '', '']);
                foreach ($items as $item) {
                    fputcsv($handle, ['    ' . $item['account']->code . ' - ' . $item['account']->name, '', number_format(max(0, $item['net']), 2, '.', '')]);
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
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo);
        $company = Company::findOrFail($companyId);

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('CamelotBooks');
        $pdf->SetTitle("Income Statement {$dateFrom} to {$dateTo}");
        $pdf->setHeaderFont(['helvetica', '', 8]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Income Statement', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "{$dateFrom} to {$dateTo}", 0, 1, 'L');
        $pdf->Ln(4);

        $colWidths = [130, 50];

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(68, 68, 68);
        $pdf->SetTextColor(255);
        $pdf->Cell($colWidths[0], 7, 'Description', 1, 0, 'L', true);
        $pdf->Cell($colWidths[1], 7, 'Amount', 1, 1, 'R', true);

        $pdf->SetTextColor(0);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Income', 1, 1, 'L', false);

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['groups']['income'] as $subType => $items) {
            foreach ($items as $item) {
                $pdf->Cell($colWidths[0], 6, '  ' . $item['account']->code . ' - ' . $item['account']->name, 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, number_format(max(0, $item['net']), 2), 1, 1, 'R');
            }
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Total Income', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['total_income'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Expenses', 1, 1, 'L', false);

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['groups']['expense'] as $subType => $items) {
            foreach ($items as $item) {
                $pdf->Cell($colWidths[0], 6, '  ' . $item['account']->code . ' - ' . $item['account']->name, 1, 0, 'L');
                $pdf->Cell($colWidths[1], 6, number_format(max(0, $item['net']), 2), 1, 1, 'R');
            }
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Total Expenses', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['total_expenses'], 2), 1, 1, 'R');

        $label = $statement['net_income'] >= 0 ? 'Net Income' : 'Net Loss';
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidths[0], 8, $label, 1, 0, 'R');
        $pdf->Cell($colWidths[1], 8, number_format(abs($statement['net_income']), 2), 1, 1, 'R');

        $filename = "income_statement_{$dateFrom}_to_{$dateTo}.pdf";
        $pdf->Output($filename, 'D');
    }
}
