<?php

namespace App\Services\FixedAssets;

use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaDepBook;
use App\Models\FixedAssets\FaDepRun;
use App\Models\FixedAssets\FaDepRunLine;
use App\Models\FixedAssets\FaHistory;
use Illuminate\Support\Facades\DB;

class DepreciationService
{
    // ── Depreciation Calculation ──────────────────

    public function calculateMonthlyDepreciation(FaDepBook $book): float
    {
        if (!$book->isFinancial()) {
            return 0;
        }

        if ($book->depreciationRemaining() <= 0) {
            return 0;
        }

        $method = $book->depreciation_method;
        $cost = (float) $book->cost;
        $residual = (float) $book->residual_value;
        $accumDep = (float) $book->accumulated_depreciation;
        $rate = (float) $book->depreciation_rate;
        $usefulLife = (int) $book->useful_life;

        switch ($method) {
            case 'straight_line':
                return $this->straightLineMonthly($cost, $residual, $usefulLife, $accumDep);

            case 'declining_balance':
                return $this->decliningBalanceMonthly($cost, $accumDep, $rate);

            case 'sum_of_years':
                return $this->sumOfYearsMonthly($cost, $residual, $usefulLife, $accumDep);

            case 'units_of_production':
                return 0;

            default:
                return $this->straightLineMonthly($cost, $residual, $usefulLife, $accumDep);
        }
    }

    private function straightLineMonthly(float $cost, float $residual, int $usefulLifeMonths, float $accumDep): float
    {
        if ($usefulLifeMonths <= 0) {
            return 0;
        }

        $depreciable = $cost - $residual;
        $monthly = $depreciable / $usefulLifeMonths;

        return round(min($monthly, max(0, $depreciable - $accumDep)), 2);
    }

    private function decliningBalanceMonthly(float $cost, float $accumDep, float $annualRate): float
    {
        if ($annualRate <= 0) {
            return 0;
        }

        $nbv = $cost - $accumDep;
        $monthlyRate = $annualRate / 12;
        $monthly = $nbv * $monthlyRate;

        return round(max(0, $monthly), 2);
    }

    private function sumOfYearsMonthly(float $cost, float $residual, int $usefulLifeMonths, float $accumDep): float
    {
        if ($usefulLifeMonths <= 0) {
            return 0;
        }

        $depreciable = $cost - $residual;
        $syd = $usefulLifeMonths * ($usefulLifeMonths + 1) / 2;
        $monthsUsed = ($accumDep / max(1, $depreciable)) * $syd;
        $remaining = max(0, $syd - $monthsUsed);
        $monthly = $depreciable * ($remaining > 0 ? (1 / $remaining) : 0);

        return round(min($monthly, max(0, $depreciable - $accumDep)), 2);
    }

    // ── Depreciation Run ──────────────────────────

    public function createRun(
        int $companyId,
        string $period,
        string $periodStart,
        string $periodEnd,
        string $bookType = FaDepBook::BOOK_FINANCIAL,
        ?int $userId = null,
    ): FaDepRun {
        return DB::transaction(function () use ($companyId, $period, $periodStart, $periodEnd, $bookType, $userId) {
            $runNumber = $this->generateRunNumber($companyId);

            $run = FaDepRun::create([
                'company_id' => $companyId,
                'run_number' => $runNumber,
                'period' => $period,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'book_type' => $bookType,
                'status' => FaDepRun::STATUS_DRAFT,
                'run_by' => $userId,
                'run_at' => now(),
            ]);

            $assets = FaAsset::forCompany($companyId)
                ->active()
                ->where('is_componentised', false)
                ->get();

            $totalDepreciation = 0;
            $assetCount = 0;

            foreach ($assets as $asset) {
                $book = $asset->depBooks()
                    ->where('book_type', $bookType)
                    ->where('is_active', true)
                    ->first();

                if (!$book) {
                    continue;
                }

                $openingNbv = (float) $book->net_book_value;
                $depreciation = $this->calculateMonthlyDepreciation($book);

                if ($depreciation <= 0) {
                    FaDepRunLine::create([
                        'company_id' => $companyId,
                        'run_id' => $run->id,
                        'asset_id' => $asset->id,
                        'dep_book_id' => $book->id,
                        'book_type' => $bookType,
                        'opening_nbv' => $openingNbv,
                        'depreciation_amount' => 0,
                        'closing_nbv' => $openingNbv,
                        'status' => FaDepRunLine::STATUS_SKIPPED,
                        'skip_reason' => 'Fully depreciated or zero amount',
                    ]);
                    continue;
                }

                $closingNbv = max(0, $openingNbv - $depreciation);

                FaDepRunLine::create([
                    'company_id' => $companyId,
                    'run_id' => $run->id,
                    'asset_id' => $asset->id,
                    'dep_book_id' => $book->id,
                    'book_type' => $bookType,
                    'opening_nbv' => $openingNbv,
                    'depreciation_amount' => $depreciation,
                    'closing_nbv' => $closingNbv,
                    'status' => FaDepRunLine::STATUS_POSTED,
                ]);

                $totalDepreciation += $depreciation;
                $assetCount++;
            }

            $run->update([
                'asset_count' => $assetCount,
                'total_depreciation' => $totalDepreciation,
            ]);

            return $run;
        });
    }

    public function postRun(FaDepRun $run): FaDepRun
    {
        if (!$run->isDraft()) {
            throw new \LogicException('Only draft runs can be posted.');
        }

        return DB::transaction(function () use ($run) {
            $lines = $run->lines()->where('status', FaDepRunLine::STATUS_POSTED)->get();

            foreach ($lines as $line) {
                $book = $line->depBook;

                $book->increment('accumulated_depreciation', $line->depreciation_amount);
                $book->decrement('net_book_value', $line->depreciation_amount);

                $asset = $line->asset;
                $asset->increment('accumulated_depreciation', $line->depreciation_amount);
                $asset->decrement('net_book_value', $line->depreciation_amount);

                $book->update(['last_run_date' => $run->period_end]);

                FaHistory::log(
                    $asset->company_id,
                    $asset->id,
                    FaHistory::EVENT_DEPRECIATED,
                    "Depreciation of {$line->depreciation_amount} posted for {$run->period}",
                    ['accumulated_depreciation' => (float) $book->accumulated_depreciation - $line->depreciation_amount],
                    ['accumulated_depreciation' => (float) $book->accumulated_depreciation],
                    $run->run_by,
                    $run->id,
                    FaDepRun::class
                );
            }

            $glService = app(AssetGlService::class);
            $je = $glService->postDepreciationRun($run->fresh(), $run->run_by);

            $run->update([
                'status' => FaDepRun::STATUS_POSTED,
                'approved_by' => $run->run_by,
                'approved_at' => now(),
            ]);

            if ($je) {
                $run->fresh()->update(['journal_entry_id' => $je->id]);
            }

            return $run->fresh();
        });
    }

    public function reverseRun(FaDepRun $run): FaDepRun
    {
        if (!$run->isPosted()) {
            throw new \LogicException('Only posted runs can be reversed.');
        }

        return DB::transaction(function () use ($run) {
            $lines = $run->lines()->where('status', FaDepRunLine::STATUS_POSTED)->get();

            foreach ($lines as $line) {
                $book = $line->depBook;
                $asset = $line->asset;

                $book->decrement('accumulated_depreciation', $line->depreciation_amount);
                $book->increment('net_book_value', $line->depreciation_amount);

                $asset->decrement('accumulated_depreciation', $line->depreciation_amount);
                $asset->increment('net_book_value', $line->depreciation_amount);

                FaHistory::log(
                    $asset->company_id,
                    $asset->id,
                    FaHistory::EVENT_DEPRECIATED,
                    "Depreciation run reversed for {$run->period}",
                    ['accumulated_depreciation' => (float) $book->accumulated_depreciation + $line->depreciation_amount],
                    ['accumulated_depreciation' => (float) $book->accumulated_depreciation],
                    $run->run_by,
                    $run->id,
                    FaDepRun::class
                );
            }

            $glService = app(AssetGlService::class);
            $reversalJe = $glService->reverseDepreciationRun($run->fresh(), $run->run_by);

            $run->update([
                'status' => FaDepRun::STATUS_REVERSED,
            ]);

            return $run->fresh();
        });
    }

    // ── Helpers ───────────────────────────────────

    private function generateRunNumber(int $companyId): string
    {
        $count = FaDepRun::forCompany($companyId)->count() + 1;
        return 'DR-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}
