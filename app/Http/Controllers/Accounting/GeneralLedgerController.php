<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = JournalEntryLine::whereHas('journalEntry', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->where('status', JournalEntry::STATUS_POSTED);
        })->with([
            'account',
            'journalEntry.branch',
        ]);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('journalEntry', function ($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        if ($request->filled('status')) {
            $query->whereHas('journalEntry', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $lines = $query->orderBy('account_id')
            ->orderBy('id')
            ->get();

        $runningBalances = [];
        $glData = [];

        $sortedLines = $lines->sortBy([
            ['account_id', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($sortedLines as $line) {
            $accountId = $line->account_id;
            $account = $line->account;

            if (!isset($runningBalances[$accountId])) {
                $runningBalances[$accountId] = (float) $account->opening_balance;
            }

            if ($account->isDebitNormal()) {
                $runningBalances[$accountId] += (float) $line->debit - (float) $line->credit;
            } else {
                $runningBalances[$accountId] += (float) $line->credit - (float) $line->debit;
            }

            $glData[] = [
                'line' => $line,
                'running_balance' => $runningBalances[$accountId],
            ];
        }

        $glPaginator = new LengthAwarePaginator($glData, count($glData), 50, $request->page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = \App\Models\CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.general-ledger.index', compact('glPaginator', 'accounts', 'branches', 'costCenters'));
    }

    public function account(Request $request, int $accountId)
    {
        $companyId = session('current_company_id');

        $account = Account::where('company_id', $companyId)
            ->findOrFail($accountId);

        $query = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('status', JournalEntry::STATUS_POSTED);
            })->with(['journalEntry.branch']);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('journalEntry', function ($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        $lines = $query->orderBy('id')->get();

        $openingBalance = (float) $account->opening_balance;
        $runningBalance = $openingBalance;

        $transactions = [];
        foreach ($lines as $line) {
            if ($account->isDebitNormal()) {
                $runningBalance += (float) $line->debit - (float) $line->credit;
            } else {
                $runningBalance += (float) $line->credit - (float) $line->debit;
            }

            $transactions[] = [
                'line' => $line,
                'running_balance' => $runningBalance,
            ];
        }

        $closingBalance = $runningBalance;

        $transactionsPaginator = new LengthAwarePaginator($transactions, count($transactions), 50, $request->page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = \App\Models\CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.general-ledger.account', compact(
            'account',
            'openingBalance',
            'transactionsPaginator',
            'closingBalance',
            'branches',
            'costCenters'
        ));
    }

    public function exportCsv(Request $request, ?int $accountId = null)
    {
        $companyId = session('current_company_id');

        $query = JournalEntryLine::whereHas('journalEntry', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->where('status', JournalEntry::STATUS_POSTED);
        })->with(['account', 'journalEntry']);

        if ($accountId) {
            $query->where('account_id', $accountId);
        } elseif ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('journalEntry', function ($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        $lines = $query->orderBy('account_id')->orderBy('id')->get();

        $runningBalances = [];
        $filename = 'general_ledger_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::streamDownload(function () use ($lines, $runningBalances) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Account Code', 'Account Name', 'Date', 'Journal Number', 'Memo', 'Debit', 'Credit', 'Running Balance']);

            $sortedLines = $lines->sortBy([
                ['account_id', 'asc'],
                ['id', 'asc'],
            ]);

            foreach ($sortedLines as $line) {
                $accId = $line->account_id;
                $account = $line->account;

                if (!isset($runningBalances[$accId])) {
                    $runningBalances[$accId] = (float) $account->opening_balance;
                }

                if ($account->isDebitNormal()) {
                    $runningBalances[$accId] += (float) $line->debit - (float) $line->credit;
                } else {
                    $runningBalances[$accId] += (float) $line->credit - (float) $line->debit;
                }

                fputcsv($handle, [
                    $account->code,
                    $account->name,
                    $line->journalEntry->date->format('Y-m-d'),
                    $line->journalEntry->journal_number,
                    $line->memo ?? $line->journalEntry->memo ?? '',
                    number_format((float) $line->debit, 2, '.', ''),
                    number_format((float) $line->credit, 2, '.', ''),
                    number_format($runningBalances[$accId], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request, int $accountId)
    {
        $companyId = session('current_company_id');

        $account = Account::where('company_id', $companyId)
            ->findOrFail($accountId);

        $query = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('status', JournalEntry::STATUS_POSTED);
            })->with(['journalEntry.branch']);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereHas('journalEntry', function ($q) use ($request) {
                $q->whereBetween('date', [$request->date_from, $request->date_to]);
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        $lines = $query->orderBy('id')->get();

        $openingBalance = (float) $account->opening_balance;
        $runningBalance = $openingBalance;
        $transactions = [];

        foreach ($lines as $line) {
            if ($account->isDebitNormal()) {
                $runningBalance += (float) $line->debit - (float) $line->credit;
            } else {
                $runningBalance += (float) $line->credit - (float) $line->debit;
            }
            $transactions[] = [
                'line' => $line,
                'running_balance' => $runningBalance,
            ];
        }

        $closingBalance = $runningBalance;
        $company = Company::findOrFail($companyId);

        $content = view('accounting.general-ledger.print', compact(
            'account', 'company', 'openingBalance', 'closingBalance', 'transactions'
        ))->render();

        return response()->view('accounting.print-export', [
            'title' => 'Account Statement - ' . $account->code . ' ' . $account->name,
            'content' => $content,
        ])->header('Content-Type', 'text/html');
    }
}
