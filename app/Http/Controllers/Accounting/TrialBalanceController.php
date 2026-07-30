<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class TrialBalanceController extends Controller
{
    private function computeTrialBalance(int $companyId, string $asOfDate, ?int $branchId = null, ?int $costCenterId = null): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        // Single grouped query: 1 query instead of N*2
        $lineQuery = JournalEntryLine::select('account_id',
                DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit'))
            ->whereHas('journalEntry', function ($q) use ($companyId, $asOfDate) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '<=', $asOfDate);
            });

        if ($branchId) {
            $lineQuery->where('branch_id', $branchId);
        }

        if ($costCenterId) {
            $lineQuery->where('cost_center_id', $costCenterId);
        }

        $lineTotals = $lineQuery->groupBy('account_id')->get()->keyBy('account_id');

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $totals = $lineTotals->get($account->id);
            $totalDebitSum = $totals ? (float) $totals->total_debit : 0;
            $totalCreditSum = $totals ? (float) $totals->total_credit : 0;

            $balance = (float) $account->opening_balance;

            if ($account->isDebitNormal()) {
                $balance += $totalDebitSum - $totalCreditSum;
            } else {
                $balance += $totalCreditSum - $totalDebitSum;
            }

            if (abs($balance) > 0.001) {
                $debitBalance = 0;
                $creditBalance = 0;

                if ($account->isDebitNormal()) {
                    if ($balance >= 0) {
                        $debitBalance = $balance;
                    } else {
                        $creditBalance = abs($balance);
                    }
                } else {
                    if ($balance >= 0) {
                        $creditBalance = $balance;
                    } else {
                        $debitBalance = abs($balance);
                    }
                }

                $trialBalance[] = [
                    'account' => $account,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];

                $totalDebit += $debitBalance;
                $totalCredit += $creditBalance;
            }
        }

        return [$trialBalance, $totalDebit, $totalCredit];
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;

        [$trialBalance, $totalDebit, $totalCredit] = $this->computeTrialBalance($companyId, $asOfDate, $branchId, $costCenterId);

        $difference = $totalDebit - $totalCredit;

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = \App\Models\CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.trial-balance.index', compact(
            'trialBalance',
            'totalDebit',
            'totalCredit',
            'difference',
            'asOfDate',
            'branches',
            'costCenters',
            'costCenterId'
        ));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;

        [$trialBalance, $totalDebit, $totalCredit] = $this->computeTrialBalance($companyId, $asOfDate, $branchId, $costCenterId);

        $filename = 'trial_balance_' . $asOfDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::streamDownload(function () use ($trialBalance, $totalDebit, $totalCredit) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Account Code', 'Account Name', 'Debit Balance', 'Credit Balance']);

            foreach ($trialBalance as $row) {
                fputcsv($handle, [
                    $row['account']->code,
                    $row['account']->name,
                    number_format($row['debit_balance'], 2, '.', ''),
                    number_format($row['credit_balance'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, ['', 'Total', number_format($totalDebit, 2, '.', ''), number_format($totalCredit, 2, '.', '')]);

            fclose($handle);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $companyId = session('current_company_id');
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->cost_center_id : null;

        [$trialBalance, $totalDebit, $totalCredit] = $this->computeTrialBalance($companyId, $asOfDate, $branchId, $costCenterId);

        $company = Company::findOrFail($companyId);
        $difference = $totalDebit - $totalCredit;

        $content = view('accounting.trial-balance.print', compact(
            'trialBalance', 'totalDebit', 'totalCredit', 'difference', 'company', 'asOfDate'
        ))->render();

        return response()->view('accounting.print-export', [
            'title' => "Trial Balance as of {$asOfDate}",
            'content' => $content,
        ])->header('Content-Type', 'text/html');
    }
}
