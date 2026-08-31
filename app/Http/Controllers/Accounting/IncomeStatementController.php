<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Accounting\Concerns\StatementPdfMeta;
use App\Models\Company;
use App\Services\Reporting\IncomeStatementService;
use App\Services\Reporting\ReportAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class IncomeStatementController extends Controller
{
    use StatementPdfMeta;

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

        $comparison = $statement['comparison'] ?? null;
        $comparisonPeriodLabel = null;
        $comparisonDateFrom = null;
        $comparisonDateTo = null;
        if ($comparison) {
            $comparisonDateFrom = $comparison['date_from'] ?? null;
            $comparisonDateTo = $comparison['date_to'] ?? null;
            $comparisonPeriodLabel = $compareMode === 'year_ago' ? 'Year Ago' : 'Prior Period';
            foreach ($statement['groups'] as $type => $subTypes) {
                foreach ($subTypes as $subType => $items) {
                    foreach ($items as &$item) {
                        $accountId = $item['account']->id;
                        $item['comparison_net'] = null;
                        if (isset($comparison['groups'][$type][$subType])) {
                            foreach ($comparison['groups'][$type][$subType] as $compItem) {
                                if ($compItem['account']->id === $accountId) {
                                    $item['comparison_net'] = $compItem['net'];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = \App\Models\CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'costCenterId', 'dateFrom', 'dateTo', 'compareMode'),
        );

        return view('accounting.income-statement.index', array_merge($statement, compact('branches', 'branchId', 'costCenters', 'costCenterId', 'dateFrom', 'dateTo', 'compareMode', 'comparisonPeriodLabel', 'comparisonDateFrom', 'comparisonDateTo', 'currencySymbol')));
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

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'costCenterId', 'dateFrom', 'dateTo'),
            outputFormat: 'csv',
        );

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

        $ytd = $this->computeYtd($companyId, $branchId, $costCenterId, $dateTo);

        ReportAuditService::log(
            userId: auth()->id(),
            companyId: $companyId,
            routeName: $request->route()->getName(),
            filters: compact('branchId', 'costCenterId', 'dateFrom', 'dateTo'),
            outputFormat: 'pdf',
        );

        $content = view('accounting.income-statement.print', array_merge($statement, $ytd, compact('company', 'dateFrom', 'dateTo')))->render();

        $periodLabel = 'For the period '
            . \Carbon\Carbon::parse($dateFrom)->format('d M Y')
            . ' — ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');

        $title = 'Income Statement';
        $meta = $this->statementPdfMeta($company, $branchId, $this->statementPreparedBy(), $title, $periodLabel);

        return response()->view('accounting.print-export', [
            'title' => "Income Statement {$dateFrom} to {$dateTo}",
            'content' => $content,
            'meta'  => $meta,
        ])->header('Content-Type', 'text/html');
    }

    /**
     * Year-to-date income statement (fiscal year start -> dateTo), mirroring
     * the IncomeStatementService net-sign convention per account.
     */
    private function computeYtd(int $companyId, ?int $branchId, ?int $costCenterId, string $dateTo): array
    {
        $yearStart = \App\Services\Reporting\FiReportContract::getFiscalYearStart($companyId, $dateTo);

        $map = \App\Models\JournalEntryLine::select(
            'account_id',
            \Illuminate\Support\Facades\DB::raw('SUM(debit) as total_debit'),
            \Illuminate\Support\Facades\DB::raw('SUM(credit) as total_credit')
        )
            ->whereHas('journalEntry', function ($q) use ($companyId, $yearStart, $dateTo) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [\App\Models\JournalEntry::STATUS_POSTED, \App\Models\JournalEntry::STATUS_REVERSED])
                    ->where('date', '>=', $yearStart)
                    ->where('date', '<=', $dateTo);
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('cost_center_id', $costCenterId))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $accounts = \App\Models\Account::where('company_id', $companyId)
            ->active()
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get();

        $ytdNets = [];
        foreach ($accounts as $account) {
            $line = $map->get($account->id);
            $debit = (float) ($line->total_debit ?? 0);
            $credit = (float) ($line->total_credit ?? 0);
            $ytdNets[$account->id] = $account->isCreditNormal() ? $credit - $debit : $debit - $credit;
        }

        $ytdIncome = 0;
        $ytdExpenses = 0;
        foreach ($accounts as $account) {
            $net = $ytdNets[$account->id] ?? 0;
            if ($account->type === 'income') {
                $ytdIncome += $net;
            } else {
                $ytdExpenses += $net;
            }
        }

        return [
            'ytd_nets'      => $ytdNets,
            'ytd_income'    => $ytdIncome,
            'ytd_expenses'  => $ytdExpenses,
            'ytd_net'       => $ytdIncome - $ytdExpenses,
            'ytd_from'      => $yearStart,
            'ytd_to'        => $dateTo,
        ];
    }
}
