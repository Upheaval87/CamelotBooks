<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reporting\CashFlowStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CashFlowController extends Controller
{
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

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.cash-flow.index', array_merge($statement, compact('branches', 'branchId', 'dateFrom', 'dateTo')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $statement = $this->service->generate($companyId, $branchId, $dateFrom, $dateTo);

        $filename = "cash_flow_{$dateFrom}_to_{$dateTo}.csv";

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
        $pdf->SetTitle("Cash Flow Statement {$dateFrom} to {$dateTo}");
        $pdf->setHeaderFont(['helvetica', '', 8]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Cash Flow Statement', 0, 1, 'L');
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
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Operating Activities', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($colWidths[0], 6, '  Net Income', 1, 0, 'L');
        $pdf->Cell($colWidths[1], 6, number_format($statement['net_income'], 2), 1, 1, 'R');

        foreach ($statement['non_cash_expenses']['items'] as $item) {
            $pdf->Cell($colWidths[0], 6, '  Add: ' . $item['account']->name, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, number_format($item['amount'], 2), 1, 1, 'R');
        }

        foreach ($statement['sections']['operating'] as $item) {
            $label = $item['change'] > 0 ? 'Increase in' : 'Decrease in';
            $pdf->Cell($colWidths[0], 6, '  ' . $label . ' ' . $item['account']->name, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, number_format($item['cash_effect'], 2), 1, 1, 'R');
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Net Cash from Operating', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['operating_total'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Investing Activities', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['sections']['investing'] as $item) {
            $label = $item['change'] > 0 ? 'Increase in' : 'Decrease in';
            $pdf->Cell($colWidths[0], 6, '  ' . $label . ' ' . $item['account']->name, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, number_format($item['cash_effect'], 2), 1, 1, 'R');
        }
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Net Cash from Investing', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['investing_total'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 7, 'Financing Activities', 1, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        foreach ($statement['sections']['financing'] as $item) {
            $label = $item['change'] > 0 ? 'Increase in' : 'Decrease in';
            $pdf->Cell($colWidths[0], 6, '  ' . $label . ' ' . $item['account']->name, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, number_format($item['cash_effect'], 2), 1, 1, 'R');
        }
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0], 7, 'Net Cash from Financing', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 7, number_format($statement['financing_total'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidths[0], 8, 'Net Change in Cash', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 8, number_format($statement['net_change'], 2), 1, 1, 'R');
        $pdf->Cell($colWidths[0], 8, 'Beginning Cash Balance', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 8, number_format($statement['beginning_cash'], 2), 1, 1, 'R');
        $pdf->Cell($colWidths[0], 8, 'Ending Cash Balance', 1, 0, 'R');
        $pdf->Cell($colWidths[1], 8, number_format($statement['ending_cash'], 2), 1, 1, 'R');

        $filename = "cash_flow_{$dateFrom}_to_{$dateTo}.pdf";
        $pdf->Output($filename, 'D');
    }
}
