<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Accounting\Concerns\StatementPdfMeta;
use App\Models\Branch;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\Reporting\EquityStatementService;
use App\Services\Reporting\FiReportContract;
use App\Services\Reporting\ReportAuditService;
use Carbon\Carbon;
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
        [
            'companyId'     => $companyId,
            'company'       => $company,
            'branchId'      => $branchId,
            'branches'      => $branches,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'presets'       => $presets,
            'activePreset'  => $activePreset,
            'showZero'      => $showZero,
        ] = $this->resolveContext($request);

        $statement = $this->service->generate($companyId, $dateFrom, $dateTo, $branchId);

        $cs = (string) SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        $dp = (int) SystemSetting::getValue('currency', 'decimal_places', $companyId, '2');
        $currency = $company->base_currency ?: 'MWK';

        $meta = $this->statementPdfMeta(
            $company,
            $branchId,
            $this->statementPreparedBy(),
            'Statement of Changes in Equity',
            $this->periodLabel($dateFrom, $dateTo)
        );
        $meta['check'] = $this->tiesOut($statement)
            ? 'Ties to the General Ledger'
            : null;

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'dateFrom', 'dateTo', 'showZero'),
        );

        return view('accounting.equity-statement.index', array_merge($statement, compact(
            'branches', 'branchId', 'dateFrom', 'dateTo', 'presets', 'activePreset',
            'showZero', 'cs', 'dp', 'currency', 'meta'
        )));
    }

    public function exportCsv(Request $request)
    {
        $ctx = $this->resolveContext($request);
        $companyId = $ctx['companyId'];
        $branchId = $ctx['branchId'];
        $dateFrom = $ctx['dateFrom'];
        $dateTo = $ctx['dateTo'];
        $showZero = $ctx['showZero'];
        $company = $ctx['company'];

        $statement = $this->service->generate($companyId, $dateFrom, $dateTo, $branchId);
        $movements = $showZero ? $statement['movements'] : $this->filterZeroRows($statement['movements']);

        $cs = (string) SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        $dp = (int) SystemSetting::getValue('currency', 'decimal_places', $companyId, '2');
        $currency = $company->base_currency ?: 'MWK';

        $filename = "equity_statement_{$dateFrom}_to_{$dateTo}.csv";

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'dateFrom', 'dateTo', 'showZero'),
            outputFormat: 'csv',
        );

        return Response::streamDownload(function () use ($movements, $statement, $cs, $dp, $currency, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Statement of Changes in Equity']);
            fputcsv($handle, ['Period', "{$dateFrom} to {$dateTo}"]);
            fputcsv($handle, ['Currency', "{$currency} ({$cs})"]);
            fputcsv($handle, []);
            fputcsv($handle, ['Code', 'Account', "Opening ({$cs})", "Movement ({$cs})", "Closing ({$cs})"]);

            foreach ($movements as $item) {
                fputcsv($handle, [
                    $item['account']->code,
                    $item['account']->name,
                    number_format($item['opening'], $dp, '.', ''),
                    number_format($item['movement'], $dp, '.', ''),
                    number_format($item['closing'], $dp, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                '', 'Net Income for the Period', '',
                number_format($statement['net_income'], $dp, '.', ''), '',
            ]);
            fputcsv($handle, [
                '', 'Total Equity',
                number_format($statement['total_opening'], $dp, '.', ''),
                number_format($statement['total_closing'] - $statement['total_opening'], $dp, '.', ''),
                number_format($statement['total_closing'], $dp, '.', ''),
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    public function exportPdf(Request $request)
    {
        $ctx = $this->resolveContext($request);
        $companyId = $ctx['companyId'];
        $branchId = $ctx['branchId'];
        $dateFrom = $ctx['dateFrom'];
        $dateTo = $ctx['dateTo'];
        $showZero = $ctx['showZero'];
        $company = $ctx['company'];

        $statement = $this->service->generate($companyId, $dateFrom, $dateTo, $branchId);
        if (! $showZero) {
            $statement['movements'] = $this->filterZeroRows($statement['movements']);
        }

        $title = 'Statement of Changes in Equity';
        $meta = $this->statementPdfMeta(
            $company,
            $branchId,
            $this->statementPreparedBy(),
            $title,
            $this->periodLabel($dateFrom, $dateTo)
        );

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'dateFrom', 'dateTo', 'showZero'),
            outputFormat: 'pdf',
        );

        $content = view('accounting.equity-statement.print', array_merge($statement, compact('company')))->render();

        return response()->view('accounting.print-export', [
            'title'   => "{$title} — {$dateFrom} to {$dateTo}",
            'content' => $content,
            'meta'    => $meta,
        ])->header('Content-Type', 'text/html');
    }

    /**
     * Shared filter resolution for all three actions.
     *
     * Currency, company identity and branch scope come from settings / the
     * user's active assignment — never hard-coded. Dates default to the
     * fiscal-year-to-date (YTD preset is on by default).
     *
     * @return array{companyId:int, company:Company, branchId:?int, branches:\Illuminate\Support\Collection, dateFrom:string, dateTo:string, presets:array, activePreset:string, showZero:bool}
     */
    private function resolveContext(Request $request): array
    {
        $companyId = (int) session('current_company_id');
        $company = Company::findOrFail($companyId);

        // §4 branch scope — from the user's active assignment, not hard-coded.
        $assignment = auth()->user()?->companyAssignments()
            ->where('company_id', $companyId)
            ->first();
        $scopedBranchIds = collect($assignment->branch_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($scopedBranchIds->isNotEmpty()) {
            $branches = $branches->whereIn('id', $scopedBranchIds)->values();
        }

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        if ($scopedBranchIds->isNotEmpty()
            && ($branchId === null || ! $scopedBranchIds->contains($branchId))) {
            $branchId = null;
        }

        // Date presets (§3.4) — server-computed, never hard-coded dates.
        $today = now();
        $fyStart = Carbon::parse(FiReportContract::getFiscalYearStart($companyId, $today->format('Y-m-d')));
        $fyEnd = $fyStart->copy()->addYear()->subDay()->format('Y-m-d');

        $presets = [
            'this_month'   => ['label' => 'This Month', 'from' => $today->copy()->startOfMonth()->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
            'this_quarter' => ['label' => 'This Quarter', 'from' => $today->copy()->startOfQuarter()->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
            'ytd'          => ['label' => 'YTD', 'from' => $fyStart->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
            'fy'           => ['label' => 'FY '.$fyStart->format('Y'), 'from' => $fyStart->format('Y-m-d'), 'to' => $fyEnd],
        ];

        // Empty-string submissions (HTML date input cleared) fall back to YTD.
        $dateFrom = $request->filled('date_from') ? (string) $request->date_from : $presets['ytd']['from'];
        $dateTo = $request->filled('date_to') ? (string) $request->date_to : $presets['ytd']['to'];

        $activePreset = 'custom';
        foreach ($presets as $key => $p) {
            if ($p['from'] === $dateFrom && $p['to'] === $dateTo) {
                $activePreset = $key;
                break;
            }
        }

        $showZero = filter_var($request->input('zero', '1'), FILTER_VALIDATE_BOOLEAN);

        return compact(
            'companyId', 'company', 'branchId', 'branches',
            'dateFrom', 'dateTo', 'presets', 'activePreset', 'showZero'
        );
    }

    private function periodLabel(string $dateFrom, string $dateTo): string
    {
        return 'For the period '
            . Carbon::parse($dateFrom)->format('d M Y')
            . ' — ' . Carbon::parse($dateTo)->format('d M Y');
    }

    private function filterZeroRows(array $movements): array
    {
        return array_values(array_filter(
            $movements,
            fn ($i) => abs((float) $i['opening']) > 0
                || abs((float) $i['movement']) > 0
                || abs((float) $i['closing']) > 0
        ));
    }

    private function tiesOut(array $statement): bool
    {
        $sumMovements = collect($statement['movements'])->sum('movement');
        return abs(
            (float) $statement['total_opening']
            + $sumMovements
            + (float) $statement['net_income']
            - (float) $statement['total_closing']
        ) < 0.01;
    }
}