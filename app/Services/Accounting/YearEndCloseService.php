<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\DefaultAccountMapping;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\Reporting\IncomeStatementService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class YearEndCloseService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private IncomeStatementService $incomeStatementService,
    ) {
    }

    public function createFiscalYear(
        int $companyId,
        string $label,
        string $startDate,
        ?int $startMonth = null,
    ): FiscalYear {
        $start = \Carbon\Carbon::parse($startDate)->startOfMonth();
        $end = $start->copy()->addYear()->subDay();

        $overlap = FiscalYear::where('company_id', $companyId)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            throw new InvalidArgumentException('The fiscal year dates overlap with an existing fiscal year.');
        }

        return DB::transaction(function () use ($companyId, $label, $start, $end, $startMonth) {
            $fy = FiscalYear::create([
                'company_id' => $companyId,
                'label' => $label,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'open',
            ]);

            $periods = $this->generatePeriods($companyId, $fy, $startMonth);

            return $fy;
        });
    }

    public function close(FiscalYear $fy, int $userId): ?JournalEntry
    {
        return DB::transaction(function () use ($fy, $userId) {
            if (!$fy->isOpen()) {
                throw new InvalidArgumentException('Only open fiscal years can be closed.');
            }

            if (!$fy->allPeriodsClosedOrLocked()) {
                throw new InvalidArgumentException(
                    'All periods in the fiscal year must be closed or locked before closing the year.'
                );
            }

            $companyId = $fy->company_id;

            $draftCount = JournalEntry::where('company_id', $companyId)
                ->where('status', JournalEntry::STATUS_DRAFT)
                ->where('date', '>=', $fy->start_date)
                ->where('date', '<=', $fy->end_date)
                ->count();

            if ($draftCount > 0) {
                throw new InvalidArgumentException(
                    "Cannot close fiscal year: {$draftCount} draft journal entry(ies) must be resolved first."
                );
            }

            $pendingCount = JournalEntry::where('company_id', $companyId)
                ->where('status', JournalEntry::STATUS_PENDING_APPROVAL)
                ->where('date', '>=', $fy->start_date)
                ->where('date', '<=', $fy->end_date)
                ->count();

            if ($pendingCount > 0) {
                throw new InvalidArgumentException(
                    "Cannot close fiscal year: {$pendingCount} journal entry(ies) pending approval must be resolved first."
                );
            }

            $netIncome = $this->incomeStatementService->computeNetIncome(
                $companyId,
                null,
                $fy->start_date->toDateString(),
                $fy->end_date->toDateString()
            );

            $retainedEarnings = DefaultAccountMapping::getAccount($companyId, 'retained_earnings');

            if (!$retainedEarnings) {
                throw new InvalidArgumentException('Retained Earnings account not found.');
            }

            $plBalances = $this->getPAndLBalancesByBranch($companyId, $fy);

            $lines = [];
            $netIncomeByBranch = [];

            foreach ($plBalances as $row) {
                $accountType = $row->account_type;
                $branchId = $row->branch_id;
                $net = $accountType === 'income'
                    ? (float) $row->total_credit - (float) $row->total_debit
                    : (float) $row->total_debit - (float) $row->total_credit;

                if (abs($net) > 0.005) {
                    $line = [
                        'account_id' => $row->account_id,
                        'debit' => $accountType === 'income'
                            ? ($net > 0 ? abs($net) : 0)
                            : ($net < 0 ? abs($net) : 0),
                        'credit' => $accountType === 'income'
                            ? ($net < 0 ? abs($net) : 0)
                            : ($net > 0 ? abs($net) : 0),
                        'memo' => 'Year-end closing - ' . $accountType,
                    ];
                    if ($branchId !== null) {
                        $line['branch_id'] = $branchId;
                    }
                    $lines[] = $line;

                    $creditMinusDebit = (float) $row->total_credit - (float) $row->total_debit;
                    $netIncomeByBranch[$branchId] = ($netIncomeByBranch[$branchId] ?? 0) + $creditMinusDebit;
                }
            }

            foreach ($netIncomeByBranch as $branchId => $branchNetIncome) {
                if (abs($branchNetIncome) > 0.005) {
                    $line = [
                        'account_id' => $retainedEarnings->id,
                        'debit' => $branchNetIncome < 0 ? abs($branchNetIncome) : 0,
                        'credit' => $branchNetIncome > 0 ? abs($branchNetIncome) : 0,
                        'memo' => 'Year-end closing - net ' .
                            ($branchNetIncome > 0 ? 'income' : 'loss') .
                            ' to retained earnings',
                    ];
                    if ($branchId !== null) {
                        $line['branch_id'] = $branchId;
                    }
                    $lines[] = $line;
                }
            }

            if (empty($lines)) {
                $fy->update([
                    'status' => 'closed',
                    'closed_by' => $userId,
                    'closed_at' => now(),
                ]);
                return null;
            }

            $totalDebit = array_sum(array_map(fn($l) => (float) $l['debit'], $lines));
            $totalCredit = array_sum(array_map(fn($l) => (float) $l['credit'], $lines));

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new InvalidArgumentException(
                    "Closing entry does not balance. Debit: " . number_format($totalDebit, 2) .
                    ", Credit: " . number_format($totalCredit, 2)
                );
            }

            $closingEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $fy->end_date->toDateString(),
                'memo' => 'Year-end closing for ' . $fy->label,
                'is_adjusting_entry' => true,
                'source_module' => 'year_end_close',
                'skip_period_validation' => true,
                'skip_inactive_account_check' => true,
                'lines' => $lines,
            ]);

            $fy->update([
                'status' => 'closed',
                'closed_by' => $userId,
                'closed_at' => now(),
                'closing_entry_id' => $closingEntry->id,
            ]);

            $fy->periods()->update(['status' => 'closed']);

            return $closingEntry;
        });
    }

    public function reopen(FiscalYear $fy, string $reason, int $userId): void
    {
        DB::transaction(function () use ($fy, $reason, $userId) {
            if (!$fy->isClosed()) {
                throw new InvalidArgumentException('Only closed fiscal years can be reopened.');
            }

            if ($fy->closing_entry_id) {
                $this->postingEngine->reverse($fy->closing_entry_id, $userId);
            }

            $fy->update([
                'status' => 'open',
                'reopen_reason' => $reason,
                'reopened_by' => $userId,
                'reopened_at' => now(),
                'closing_entry_id' => null,
            ]);

            $fy->periods()->where('status', 'closed')->update(['status' => 'open']);
        });
    }

    private function getPAndLBalancesByBranch(int $companyId, FiscalYear $fy): \Illuminate\Support\Collection
    {
        return \App\Models\JournalEntryLine::whereHas('journalEntry', function ($q) use ($companyId, $fy) {
            $q->where('company_id', $companyId)
                ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                ->where('date', '>=', $fy->start_date)
                ->where('date', '<=', $fy->end_date);
        })
            ->whereHas('account', function ($q) {
                $q->whereIn('type', ['income', 'expense']);
            })
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->select(
                'journal_entry_lines.account_id',
                'journal_entry_lines.branch_id',
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit')
            )
            ->groupBy('journal_entry_lines.account_id', 'journal_entry_lines.branch_id', 'accounts.type')
            ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.005 OR ABS(SUM(credit) - SUM(debit)) > 0.005')
            ->get();
    }

    private function generatePeriods(int $companyId, FiscalYear $fy, ?int $startMonth): array
    {
        $periods = [];
        $start = $fy->start_date->copy();

        for ($i = 0; $i < 12; $i++) {
            $periodStart = $start->copy()->addMonths($i)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();

            if ($periodEnd > $fy->end_date) {
                $periodEnd = $fy->end_date->copy();
            }
            if ($periodStart < $fy->start_date) {
                $periodStart = $fy->start_date->copy();
            }

            $periods[] = AccountingPeriod::create([
                'company_id' => $companyId,
                'fiscal_year_id' => $fy->id,
                'label' => $periodStart->format('F Y'),
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'status' => 'open',
            ]);
        }

        return $periods;
    }
}
