<?php

namespace App\Services\Accounting\FixedAssets;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\AccountingPeriod;
use App\Models\Asset;
use App\Models\AssetDepreciationBook;
use App\Models\DepreciationRun;
use App\Models\DepreciationScheduleEntry;
use App\Services\Accounting\FixedAssets\Calculators\DoubleDecliningCalculator;
use App\Services\Accounting\FixedAssets\Calculators\ReducingBalanceCalculator;
use App\Services\Accounting\FixedAssets\Calculators\StraightLineCalculator;
use App\Services\Accounting\FixedAssets\Calculators\SumOfYearsDigitsCalculator;
use App\Services\Accounting\FixedAssets\Calculators\UnitsOfProductionCalculator;
use App\Services\Accounting\JournalPostingEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DepreciationEngine
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function projectSchedule(Asset $asset, AssetDepreciationBook $book): array
    {
        $calculator = $this->getDepreciationCalculator($book->depreciation_method);

        $usefulLife = (int) $book->useful_life;
        $currentCost = (float) $book->current_cost;
        $residualValue = (float) $book->residual_value;
        $accumulatedDepreciation = (float) $book->accumulated_depreciation;
        $accumulatedImpairment = (float) $book->accumulated_impairment;

        $existingEntries = $book->scheduleEntries()
            ->where('is_posted', false)
            ->orderBy('period_number')
            ->get();

        foreach ($existingEntries as $entry) {
            $entry->delete();
        }

        $scheduleEntries = [];
        $runningAccumulated = $accumulatedDepreciation;
        $openingNbv = (float) $book->net_book_value;

        $startDate = $asset->in_service_date
            ? Carbon::parse($asset->in_service_date)
            : Carbon::parse($asset->acquisition_date);

        for ($period = 1; $period <= $usefulLife; $period++) {
            $periodStartDate = $startDate->copy()->addMonths($period - 1);
            $periodEndDate = $startDate->copy()->addMonths($period)->subDay();

            $charge = $calculator->calculatePeriodCharge(
                $currentCost,
                $residualValue,
                $runningAccumulated,
                $accumulatedImpairment,
                $usefulLife,
                $period,
                [
                    'depreciation_rate' => (float) $book->depreciation_rate,
                    'total_estimated_units' => (float) ($book->total_estimated_units ?? 0),
                    'units_used' => 0,
                ]
            );

            $runningAccumulated = round($runningAccumulated + $charge, 2);
            $closingNbv = round($currentCost - $runningAccumulated - $accumulatedImpairment, 2);

            $entry = DepreciationScheduleEntry::create([
                'asset_id' => $asset->id,
                'asset_depreciation_book_id' => $book->id,
                'depreciation_run_id' => null,
                'period_number' => $period,
                'period_start_date' => $periodStartDate->toDateString(),
                'period_end_date' => $periodEndDate->toDateString(),
                'opening_nbv' => number_format($openingNbv, 2, '.', ''),
                'depreciation_charge' => number_format($charge, 2, '.', ''),
                'accumulated_depreciation' => number_format($runningAccumulated, 2, '.', ''),
                'closing_nbv' => number_format($closingNbv, 2, '.', ''),
                'units_used' => 0,
                'is_posted' => false,
                'posted_at' => null,
                'journal_entry_id' => null,
            ]);

            $scheduleEntries[] = $entry;
            $openingNbv = $closingNbv;
        }

        return $scheduleEntries;
    }

    public function runDepreciation(int $companyId, string $period, int $userId): DepreciationRun
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new InvalidArgumentException("Invalid period format '{$period}'. Expected YYYY-MM.");
        }

        $periodStart = Carbon::parse($period . '-01');
        $periodEnd = $periodStart->copy()->endOfMonth();

        $periodRecord = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $periodEnd)
            ->where('end_date', '>=', $periodStart)
            ->first();

        $isLocked = false;
        if ($periodRecord && $periodRecord->isLocked()) {
            $isLocked = true;
        }

        $books = AssetDepreciationBook::whereHas('asset', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->where('status', Asset::STATUS_ACTIVE);
        })
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->where('status', 'active')
            ->get();

        $skipReasons = [];
        $processedCount = 0;
        $skippedCount = 0;
        $totalDepreciation = 0;
        $summarizedLines = [];

        foreach ($books as $book) {
            $asset = $book->asset;

            if ($isLocked) {
                $skipReasons[] = [
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'reason' => 'Period is locked',
                ];
                $skippedCount++;
                continue;
            }

            $lastDepreciationDate = $book->last_depreciation_date
                ? Carbon::parse($book->last_depreciation_date)
                : null;

            if ($lastDepreciationDate && !$lastDepreciationDate->lt($periodStart)) {
                $skipReasons[] = [
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'reason' => 'Already depreciated for this period',
                ];
                $skippedCount++;
                continue;
            }

            $calculator = $this->getDepreciationCalculator($book->depreciation_method);

            $scheduleEntry = $book->scheduleEntries()
                ->where('is_posted', false)
                ->orderBy('period_number')
                ->first();

            $unitsUsed = 0;
            if (strtolower($book->depreciation_method) === 'units_of_production') {
                $usageEntry = \App\Models\UnitsOfProductionUsageEntry::where('asset_id', $asset->id)
                    ->where('period_start_date', '<=', $periodEnd)
                    ->where('period_end_date', '>=', $periodStart)
                    ->first();

                if ($usageEntry) {
                    $unitsUsed = (float) $usageEntry->units_used;
                }
            }

            $charge = $calculator->calculatePeriodCharge(
                (float) $book->current_cost,
                (float) $book->residual_value,
                (float) $book->accumulated_depreciation,
                (float) $book->accumulated_impairment,
                (int) $book->useful_life,
                $scheduleEntry ? (int) $scheduleEntry->period_number : 1,
                [
                    'depreciation_rate' => (float) $book->depreciation_rate,
                    'total_estimated_units' => (float) ($book->total_estimated_units ?? 0),
                    'units_used' => $unitsUsed,
                ]
            );

            if ($charge <= 0) {
                $skipReasons[] = [
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'reason' => 'No depreciation charge calculated (fully depreciated or zero charge)',
                ];
                $skippedCount++;
                continue;
            }

            $expenseAccountId = $this->resolveExpenseAccountId($asset, $book);
            $accumulatedDepAccountId = $this->resolveAccumulatedDepreciationAccountId($asset, $book);
            $branchId = $asset->branch_id;
            $costCenterId = $asset->cost_center_id;

            $expenseKey = "{$expenseAccountId}_{$branchId}_{$costCenterId}";
            if (!isset($summarizedLines[$expenseKey])) {
                $summarizedLines[$expenseKey] = [
                    'expense_account_id' => $expenseAccountId,
                    'accumulated_dep_account_id' => $accumulatedDepAccountId,
                    'branch_id' => $branchId,
                    'cost_center_id' => $costCenterId,
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'book_ids' => [],
                ];
            }

            $summarizedLines[$expenseKey]['total_debit'] = round(
                $summarizedLines[$expenseKey]['total_debit'] + $charge, 2
            );
            $summarizedLines[$expenseKey]['total_credit'] = round(
                $summarizedLines[$expenseKey]['total_credit'] + $charge, 2
            );
            $summarizedLines[$expenseKey]['book_ids'][] = $book->id;

            $newAccumulatedDep = round((float) $book->accumulated_depreciation + $charge, 2);
            $newNbv = round((float) $book->current_cost - $newAccumulatedDep - (float) $book->accumulated_impairment, 2);

            $book->update([
                'accumulated_depreciation' => number_format($newAccumulatedDep, 2, '.', ''),
                'net_book_value' => number_format($newNbv, 2, '.', ''),
                'last_depreciation_date' => $periodEnd->toDateString(),
            ]);

            if ($scheduleEntry) {
                $scheduleEntry->update([
                    'depreciation_charge' => number_format($charge, 2, '.', ''),
                    'accumulated_depreciation' => number_format($newAccumulatedDep, 2, '.', ''),
                    'closing_nbv' => number_format($newNbv, 2, '.', ''),
                    'units_used' => $unitsUsed,
                    'is_posted' => true,
                    'posted_at' => now(),
                ]);
            }

            $processedCount++;
            $totalDepreciation = round($totalDepreciation + $charge, 2);
        }

        $runNumber = $this->generateRunNumber($companyId);

        $journalEntry = null;
        if (!empty($summarizedLines)) {
            $jeLines = [];
            foreach ($summarizedLines as $summary) {
                $expenseAccount = Account::find($summary['expense_account_id']);
                $accDepAccount = Account::find($summary['accumulated_dep_account_id']);

                $jeLines[] = [
                    'account_id' => $summary['expense_account_id'],
                    'debit' => $summary['total_debit'],
                    'credit' => 0,
                    'memo' => "Depreciation expense for {$period}",
                    'entity_type' => DepreciationRun::class,
                    'branch_id' => $summary['branch_id'],
                    'cost_center_id' => $summary['cost_center_id'],
                ];

                $jeLines[] = [
                    'account_id' => $summary['accumulated_dep_account_id'],
                    'debit' => 0,
                    'credit' => $summary['total_credit'],
                    'memo' => "Accumulated depreciation for {$period}",
                    'entity_type' => DepreciationRun::class,
                    'branch_id' => $summary['branch_id'],
                    'cost_center_id' => $summary['cost_center_id'],
                ];
            }

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $periodEnd->toDateString(),
                'source_module' => 'depreciation_run',
                'reference' => $runNumber,
                'memo' => "Depreciation run for {$period}",
                'lines' => $jeLines,
            ]);
        }

        $run = DepreciationRun::create([
            'company_id' => $companyId,
            'run_number' => $runNumber,
            'period' => $period,
            'period_start_date' => $periodStart->toDateString(),
            'period_end_date' => $periodEnd->toDateString(),
            'status' => $journalEntry ? DepreciationRun::STATUS_POSTED : DepreciationRun::STATUS_DRAFT,
            'total_depreciation_amount' => number_format($totalDepreciation, 2, '.', ''),
            'assets_processed' => $processedCount,
            'assets_skipped' => $skippedCount,
            'skip_reasons' => $skipReasons,
            'journal_entry_id' => $journalEntry?->id,
            'created_by' => $userId,
            'posted_by' => $journalEntry ? $userId : null,
            'posted_at' => $journalEntry ? now() : null,
        ]);

        AccountAuditLog::create([
            'company_id' => $companyId,
            'journalable_type' => DepreciationRun::class,
            'journalable_id' => $run->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => [
                'period' => $period,
                'total_depreciation' => $totalDepreciation,
                'assets_processed' => $processedCount,
                'assets_skipped' => $skippedCount,
            ],
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        return $run;
    }

    public function trueUpToDisposal(Asset $asset, string $disposalDate): float
    {
        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        if (!$financialBook) {
            throw new InvalidArgumentException('Financial depreciation book not found for this asset.');
        }

        $lastDepreciationDate = $financialBook->last_depreciation_date
            ? Carbon::parse($financialBook->last_depreciation_date)
            : null;

        $disposal = Carbon::parse($disposalDate);

        if ($lastDepreciationDate && !$lastDepreciationDate->lt($disposal)) {
            return 0.0;
        }

        $calculator = $this->getDepreciationCalculator($financialBook->depreciation_method);

        $scheduleEntry = $financialBook->scheduleEntries()
            ->where('is_posted', false)
            ->orderBy('period_number')
            ->first();

        $charge = $calculator->calculatePeriodCharge(
            (float) $financialBook->current_cost,
            (float) $financialBook->residual_value,
            (float) $financialBook->accumulated_depreciation,
            (float) $financialBook->accumulated_impairment,
            (int) $financialBook->useful_life,
            $scheduleEntry ? (int) $scheduleEntry->period_number : 1,
            [
                'depreciation_rate' => (float) $financialBook->depreciation_rate,
                'total_estimated_units' => (float) ($financialBook->total_estimated_units ?? 0),
                'units_used' => 0,
            ]
        );

        if ($charge <= 0) {
            return 0.0;
        }

        $newAccumulatedDep = round((float) $financialBook->accumulated_depreciation + $charge, 2);
        $newNbv = round((float) $financialBook->current_cost - $newAccumulatedDep - (float) $financialBook->accumulated_impairment, 2);

        $financialBook->update([
            'accumulated_depreciation' => number_format($newAccumulatedDep, 2, '.', ''),
            'net_book_value' => number_format($newNbv, 2, '.', ''),
            'last_depreciation_date' => $disposal->toDateString(),
        ]);

        if ($scheduleEntry) {
            $scheduleEntry->update([
                'depreciation_charge' => number_format($charge, 2, '.', ''),
                'accumulated_depreciation' => number_format($newAccumulatedDep, 2, '.', ''),
                'closing_nbv' => number_format($newNbv, 2, '.', ''),
                'is_posted' => true,
                'posted_at' => now(),
            ]);
        }

        $expenseAccountId = $this->resolveExpenseAccountId($asset, $financialBook);
        $accumulatedDepAccountId = $this->resolveAccumulatedDepreciationAccountId($asset, $financialBook);

        $this->postingEngine->post([
            'company_id' => $asset->company_id,
            'created_by' => $asset->created_by,
            'date' => $disposalDate,
            'source_module' => 'fixed_asset_trueup',
            'reference' => $asset->asset_code,
            'memo' => "True-up depreciation for {$asset->asset_code} to disposal date",
            'lines' => [
                [
                    'account_id' => $expenseAccountId,
                    'debit' => $charge,
                    'credit' => 0,
                    'memo' => "True-up depreciation expense for {$asset->asset_code}",
                    'entity_type' => Asset::class,
                    'entity_id' => $asset->id,
                    'branch_id' => $asset->branch_id,
                    'cost_center_id' => $asset->cost_center_id,
                ],
                [
                    'account_id' => $accumulatedDepAccountId,
                    'debit' => 0,
                    'credit' => $charge,
                    'memo' => "True-up accumulated depreciation for {$asset->asset_code}",
                    'entity_type' => Asset::class,
                    'entity_id' => $asset->id,
                    'branch_id' => $asset->branch_id,
                    'cost_center_id' => $asset->cost_center_id,
                ],
            ],
        ]);

        return $charge;
    }

    public function recomputeScheduleAfterEvent(Asset $asset, AssetDepreciationBook $book): void
    {
        $postedEntries = $book->scheduleEntries()
            ->where('is_posted', true)
            ->orderByDesc('period_number')
            ->first();

        $postedCount = $book->scheduleEntries()
            ->where('is_posted', true)
            ->count();

        $book->scheduleEntries()
            ->where('is_posted', false)
            ->delete();

        $calculator = $this->getDepreciationCalculator($book->depreciation_method);

        $usefulLife = (int) $book->useful_life;
        $currentCost = (float) $book->current_cost;
        $residualValue = (float) $book->residual_value;
        $accumulatedDepreciation = (float) $book->accumulated_depreciation;
        $accumulatedImpairment = (float) $book->accumulated_impairment;

        $lastPostedDate = $postedEntries
            ? Carbon::parse($postedEntries->period_end_date)->addDay()
            : ($asset->in_service_date ? Carbon::parse($asset->in_service_date) : Carbon::parse($asset->acquisition_date));

        $runningAccumulated = $accumulatedDepreciation;
        $openingNbv = (float) $book->net_book_value;

        $remainingPeriods = $usefulLife - $postedCount;
        if ($remainingPeriods <= 0) {
            return;
        }

        for ($period = $postedCount + 1; $period <= $usefulLife; $period++) {
            $periodOffset = $period - $postedCount - 1;
            $periodStartDate = $lastPostedDate->copy()->addMonths($periodOffset);
            $periodEndDate = $lastPostedDate->copy()->addMonths($periodOffset + 1)->subDay();

            $charge = $calculator->calculatePeriodCharge(
                $currentCost,
                $residualValue,
                $runningAccumulated,
                $accumulatedImpairment,
                $usefulLife,
                $period,
                [
                    'depreciation_rate' => (float) $book->depreciation_rate,
                    'total_estimated_units' => (float) ($book->total_estimated_units ?? 0),
                    'units_used' => 0,
                ]
            );

            $runningAccumulated = round($runningAccumulated + $charge, 2);
            $closingNbv = round($currentCost - $runningAccumulated - $accumulatedImpairment, 2);

            DepreciationScheduleEntry::create([
                'asset_id' => $asset->id,
                'asset_depreciation_book_id' => $book->id,
                'depreciation_run_id' => null,
                'period_number' => $period,
                'period_start_date' => $periodStartDate->toDateString(),
                'period_end_date' => $periodEndDate->toDateString(),
                'opening_nbv' => number_format($openingNbv, 2, '.', ''),
                'depreciation_charge' => number_format($charge, 2, '.', ''),
                'accumulated_depreciation' => number_format($runningAccumulated, 2, '.', ''),
                'closing_nbv' => number_format($closingNbv, 2, '.', ''),
                'units_used' => 0,
                'is_posted' => false,
                'posted_at' => null,
                'journal_entry_id' => null,
            ]);

            $openingNbv = $closingNbv;
        }
    }

    public function getDepreciationCalculator(string $method): DepreciationMethodInterface
    {
        return match (strtolower($method)) {
            'straight_line' => new StraightLineCalculator(),
            'reducing_balance' => new ReducingBalanceCalculator(),
            'double_declining' => new DoubleDecliningCalculator(),
            'sum_of_years_digits' => new SumOfYearsDigitsCalculator(),
            'units_of_production' => new UnitsOfProductionCalculator(),
            default => throw new InvalidArgumentException("Unknown depreciation method: {$method}"),
        };
    }

    protected function resolveExpenseAccountId(Asset $asset, AssetDepreciationBook $book): int
    {
        if ($asset->depreciation_expense_account_id) {
            return $asset->depreciation_expense_account_id;
        }

        if ($asset->category && $asset->category->depreciation_expense_account_id) {
            return $asset->category->depreciation_expense_account_id;
        }

        throw new InvalidArgumentException("Depreciation expense account not found for asset {$asset->asset_code}.");
    }

    protected function resolveAccumulatedDepreciationAccountId(Asset $asset, AssetDepreciationBook $book): int
    {
        if ($asset->accumulated_depreciation_account_id) {
            return $asset->accumulated_depreciation_account_id;
        }

        if ($asset->category && $asset->category->accumulated_depreciation_account_id) {
            return $asset->category->accumulated_depreciation_account_id;
        }

        throw new InvalidArgumentException("Accumulated depreciation account not found for asset {$asset->asset_code}.");
    }

    protected function generateRunNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'DEPR-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastRun = DepreciationRun::where('company_id', $companyId)
            ->where('run_number', 'like', $prefix . '%')
            ->orderByDesc('run_number')
            ->first();

        if ($lastRun) {
            $lastSequence = (int) substr($lastRun->run_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }
}
