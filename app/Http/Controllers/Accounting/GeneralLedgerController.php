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

        return view('accounting.general-ledger.index', compact('glPaginator', 'accounts', 'branches'));
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

        return view('accounting.general-ledger.account', compact(
            'account',
            'openingBalance',
            'transactionsPaginator',
            'closingBalance',
            'branches'
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

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('CamelotBooks');
        $pdf->SetTitle('Account Statement - ' . $account->code . ' ' . $account->name);
        $pdf->setHeaderFont(['helvetica', '', 8]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Account Statement', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Account: ' . $account->code . ' - ' . $account->name, 0, 1, 'L');
        $pdf->Cell(0, 6, 'Type: ' . ucfirst($account->type), 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Opening Balance: ' . number_format($openingBalance, 2), 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('helvetica', 'B', 9);
        $colWidths = [25, 30, 25, 50, 30, 30, 35];
        $tableHeaders = ['Date', 'Journal #', 'Branch', 'Memo', 'Debit', 'Credit', 'Balance'];

        $pdf->SetFillColor(68, 68, 68);
        $pdf->SetTextColor(255);
        for ($i = 0; $i < count($tableHeaders); $i++) {
            $pdf->Cell($colWidths[$i], 7, $tableHeaders[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0);

        foreach ($transactions as $txn) {
            $line = $txn['line'];
            $row = [
                $line->journalEntry->date->format('Y-m-d'),
                $line->journalEntry->journal_number,
                $line->journalEntry->branch->name ?? '-',
                mb_substr($line->memo ?? $line->journalEntry->memo ?? '', 0, 30),
                (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '',
                (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '',
                number_format($txn['running_balance'], 2),
            ];
            for ($i = 0; $i < count($row); $i++) {
                $align = ($i >= 4) ? 'R' : 'L';
                $pdf->Cell($colWidths[$i], 6, $row[$i], 1, 0, $align);
            }
            $pdf->Ln();
        }

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, '', 0, 1);
        $pdf->Cell(190, 8, 'Closing Balance:', 1, 0, 'R');
        $pdf->Cell(35, 8, number_format($closingBalance, 2), 1, 1, 'R');

        $filename = 'account_statement_' . $account->code . '_' . now()->format('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
    }
}
