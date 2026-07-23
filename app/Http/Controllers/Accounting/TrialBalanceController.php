<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TrialBalanceController extends Controller
{
    private function computeTrialBalance(int $companyId, string $asOfDate, ?int $branchId = null): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $lineQuery = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($companyId, $asOfDate) {
                    $q->where('company_id', $companyId)
                        ->where('status', JournalEntry::STATUS_POSTED)
                        ->where('date', '<=', $asOfDate);
                });

            if ($branchId) {
                $lineQuery->where('branch_id', $branchId);
            }

            $totalDebitSum = (float) $lineQuery->sum('debit');
            $totalCreditSum = (float) $lineQuery->sum('credit');

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

        [$trialBalance, $totalDebit, $totalCredit] = $this->computeTrialBalance($companyId, $asOfDate, $branchId);

        $difference = $totalDebit - $totalCredit;

        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.trial-balance.index', compact(
            'trialBalance',
            'totalDebit',
            'totalCredit',
            'difference',
            'asOfDate',
            'branches'
        ));
    }

    public function exportCsv(Request $request)
    {
        $companyId = session('current_company_id');
        $asOfDate = $request->input('as_of_date', now()->format('Y-m-d'));
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        [$trialBalance, $totalDebit, $totalCredit] = $this->computeTrialBalance($companyId, $asOfDate, $branchId);

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

        [$trialBalance, $totalDebit, $totalCredit] = $this->computeTrialBalance($companyId, $asOfDate, $branchId);

        $company = Company::findOrFail($companyId);

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('CamelotBooks');
        $pdf->SetTitle('Trial Balance - ' . $asOfDate);
        $pdf->setHeaderFont(['helvetica', '', 8]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Trial Balance', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'As of: ' . $asOfDate, 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('helvetica', 'B', 9);
        $colWidths = [40, 80, 35, 35];
        $tableHeaders = ['Account Code', 'Account Name', 'Debit Balance', 'Credit Balance'];

        $pdf->SetFillColor(68, 68, 68);
        $pdf->SetTextColor(255);
        for ($i = 0; $i < count($tableHeaders); $i++) {
            $pdf->Cell($colWidths[$i], 7, $tableHeaders[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0);

        foreach ($trialBalance as $row) {
            $pdf->Cell($colWidths[0], 6, $row['account']->code, 1, 0, 'L');
            $pdf->Cell($colWidths[1], 6, $row['account']->name, 1, 0, 'L');
            $pdf->Cell($colWidths[2], 6, $row['debit_balance'] > 0 ? number_format($row['debit_balance'], 2) : '', 1, 0, 'R');
            $pdf->Cell($colWidths[3], 6, $row['credit_balance'] > 0 ? number_format($row['credit_balance'], 2) : '', 1, 0, 'R');
            $pdf->Ln();
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colWidths[0] + $colWidths[1], 8, 'Totals', 1, 0, 'R');
        $pdf->Cell($colWidths[2], 8, number_format($totalDebit, 2), 1, 0, 'R');
        $pdf->Cell($colWidths[3], 8, number_format($totalCredit, 2), 1, 0, 'R');
        $pdf->Ln();

        $difference = $totalDebit - $totalCredit;
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Difference: ' . number_format($difference, 2), 0, 1, 'R');

        $filename = 'trial_balance_' . $asOfDate . '.pdf';
        $pdf->Output($filename, 'D');
    }
}
