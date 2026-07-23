<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
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

            $retainedEarnings = Account::where('company_id', $companyId)
                ->where('code', '3100')
                ->first();

            if (!$retainedEarnings) {
                throw new InvalidArgumentException('Retained Earnings account (3100) not found.');
            }

            $revenueAccounts = Account::where('company_id', $companyId)
                ->where('type', 'income')
                ->where('is_active', true)
                ->get();

            $expenseAccounts = Account::where('company_id', $companyId)
                ->where('type', 'expense')
                ->where('is_active', true)
                ->get();

            $lines = [];

            foreach ($revenueAccounts as $account) {
                $balance = $this->computeAccountBalance($account, $companyId, $fy);
                if (abs($balance) > 0.005) {
                    $lines[] = [
                        'account_id' => $account->id,
                        'debit' => $balance > 0 ? abs($balance) : 0,
                        'credit' => $balance < 0 ? abs($balance) : 0,
                        'memo' => 'Year-end closing - revenue',
                    ];
                }
            }

            foreach ($expenseAccounts as $account) {
                $balance = $this->computeAccountBalance($account, $companyId, $fy);
                if (abs($balance) > 0.005) {
                    $lines[] = [
                        'account_id' => $account->id,
                        'debit' => $balance < 0 ? abs($balance) : 0,
                        'credit' => $balance > 0 ? abs($balance) : 0,
                        'memo' => 'Year-end closing - expense',
                    ];
                }
            }

            if (abs($netIncome) > 0.005) {
                if ($netIncome > 0) {
                    $lines[] = [
                        'account_id' => $retainedEarnings->id,
                        'debit' => 0,
                        'credit' => abs($netIncome),
                        'memo' => 'Year-end closing - net income to retained earnings',
                    ];
                } else {
                    $lines[] = [
                        'account_id' => $retainedEarnings->id,
                        'debit' => abs($netIncome),
                        'credit' => 0,
                        'memo' => 'Year-end closing - net loss to retained earnings',
                    ];
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

    private function computeAccountBalance(Account $account, int $companyId, FiscalYear $fy): float
    {
        $result = \App\Models\JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($companyId, $fy) {
                $q->where('company_id', $companyId)
                    ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                    ->where('date', '>=', $fy->start_date)
                    ->where('date', '<=', $fy->end_date);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $debit = (float) ($result->total_debit ?? 0);
        $credit = (float) ($result->total_credit ?? 0);

        if ($account->isCreditNormal()) {
            return $credit - $debit;
        }
        return $debit - $credit;
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
