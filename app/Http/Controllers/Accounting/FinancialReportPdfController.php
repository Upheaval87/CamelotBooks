<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Reporting\FiReportPdfService;
use App\Services\Reporting\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * STAGE 3 — Financial Report PDF Controller
 *
 * §9 shared editorial template for all 5 reports.
 * Routes: accounting.reports.financial.{report}/pdf
 */
class FinancialReportPdfController extends Controller
{
    public function __construct(private FiReportPdfService $pdfService)
    {
    }

    /**
     * §9.8 — Generate PDF for any of the 5 financial reports.
     */
    public function generate(Request $request, string $report): \Symfony\Component\HttpFoundation\Response
    {
        $params = $request->only([
            'branch_id', 'date_from', 'date_to', 'as_of_date',
            'cost_center_id', 'compare_mode',
        ]);

        $data = match($report) {
            'income'   => $this->pdfService->incomeStatement($params),
            'position' => $this->pdfService->balanceSheet($params),
            'cashflow' => $this->pdfService->cashFlow($params),
            'ar-aging' => $this->pdfService->arAging($params),
            'ap-aging' => $this->pdfService->apAging($params),
            default    => abort(404),
        };

        $data['pdfMode'] = true;

        $filename = $this->buildFilename($report, $params);

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: session('current_company_id'),
            routeName: 'accounting.reports.financial.' . $report . '.pdf',
            filters: array_merge($params, ['report' => $report]),
            outputFormat: 'pdf',
        );

        return Pdf::loadView('pdf.financial-report', $data)
            ->setPaper('a4')
            ->download($filename);
    }

    /**
     * §9.8 — Stream/preview PDF in browser.
     */
    public function preview(Request $request, string $report): \Symfony\Component\HttpFoundation\Response
    {
        $params = $request->only([
            'branch_id', 'date_from', 'date_to', 'as_of_date',
            'cost_center_id', 'compare_mode',
        ]);

        $data = match($report) {
            'income'   => $this->pdfService->incomeStatement($params),
            'position' => $this->pdfService->balanceSheet($params),
            'cashflow' => $this->pdfService->cashFlow($params),
            'ar-aging' => $this->pdfService->arAging($params),
            'ap-aging' => $this->pdfService->apAging($params),
            default    => abort(404),
        };

        $data['pdfMode'] = true;

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: session('current_company_id'),
            routeName: 'accounting.reports.financial.' . $report . '.pdf',
            filters: array_merge($params, ['report' => $report]),
            outputFormat: 'pdf',
        );

        $pdf = Pdf::loadView('pdf.financial-report', $data)
            ->setPaper('a4');

        return $pdf->stream($this->buildFilename($report, $params));
    }

    private function buildFilename(string $report, array $params): string
    {
        $label = match($report) {
            'income'   => 'income-statement',
            'position' => 'balance-sheet',
            'cashflow' => 'cash-flow',
            'ar-aging' => 'ar-aging',
            'ap-aging' => 'ap-aging',
            default    => $report,
        };

        $date = $params['as_of_date'] ?? $params['date_to'] ?? now()->format('Y-m-d');

        return "{$label}-{$date}.pdf";
    }
}
