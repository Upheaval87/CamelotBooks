<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reporting\AgingReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AgingReportController extends Controller
{
    public function __construct(private AgingReportService $service)
    {
    }

    public function arSummary(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $result = $this->service->arAging($companyId, $branchId, $asOfDate);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.aging.ar-summary', array_merge($result, compact('branches', 'branchId', 'asOfDate')));
    }

    public function arDetail(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $result = $this->service->arAgingDetail($companyId, $branchId, $asOfDate);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.aging.ar-detail', array_merge($result, compact('branches', 'branchId', 'asOfDate')));
    }

    public function apSummary(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $result = $this->service->apAging($companyId, $branchId, $asOfDate);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.aging.ap-summary', array_merge($result, compact('branches', 'branchId', 'asOfDate')));
    }

    public function apDetail(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $result = $this->service->apAgingDetail($companyId, $branchId, $asOfDate);

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.aging.ap-detail', array_merge($result, compact('branches', 'branchId', 'asOfDate')));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $type = $request->input('type', 'ar');

        if ($type === 'ar') {
            $result = $this->service->arAging($companyId, $branchId, $asOfDate);
            $filename = "ar_aging_{$asOfDate}.csv";
        } else {
            $result = $this->service->apAging($companyId, $branchId, $asOfDate);
            $filename = "ap_aging_{$asOfDate}.csv";
        }

        $headers = $type === 'ar' ? ['Customer', 'Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total'] : ['Vendor', 'Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total'];
        $rows = $type === 'ar' ? $result['customers'] : $result['vendors'];
        $nameKey = $type === 'ar' ? 'customer_name' : 'vendor_name';
        $totals = $result['totals'];

        return Response::streamDownload(function () use ($headers, $rows, $nameKey, $totals) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row[$nameKey],
                    number_format($row['current'], 2, '.', ''),
                    number_format($row['days_1_30'], 2, '.', ''),
                    number_format($row['days_31_60'], 2, '.', ''),
                    number_format($row['days_61_90'], 2, '.', ''),
                    number_format($row['days_90_plus'], 2, '.', ''),
                    number_format($row['total'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, [
                'Total',
                number_format($totals['current'], 2, '.', ''),
                number_format($totals['days_1_30'], 2, '.', ''),
                number_format($totals['days_31_60'], 2, '.', ''),
                number_format($totals['days_61_90'], 2, '.', ''),
                number_format($totals['days_90_plus'], 2, '.', ''),
                number_format($totals['total'], 2, '.', ''),
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
