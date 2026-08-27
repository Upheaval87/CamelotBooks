<?php

namespace App\Services\FixedAssets;

use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaDisposal;
use App\Models\FixedAssets\FaImpairment;
use App\Models\FixedAssets\FaRevaluation;
use App\Models\FixedAssets\FaHistory;
use Illuminate\Support\Facades\DB;

class DisposalService
{
    // ── Disposal ──────────────────────────────────

    public function createDisposal(FaAsset $asset, array $data, int $userId): FaDisposal
    {
        if ($asset->isDisposed()) {
            throw new \LogicException('Asset is already disposed.');
        }

        return DB::transaction(function () use ($asset, $data, $userId) {
            $costAcq = (float) $asset->acquisition_cost;
            $accumDep = (float) $asset->accumulated_depreciation;
            $accumImp = (float) $asset->accumulated_impairment;
            $nbv = $costAcq - $accumDep - $accumImp;
            $proceeds = (float) ($data['proceeds_amount'] ?? 0);
            $disposalCost = (float) ($data['disposal_cost'] ?? 0);
            $netProceeds = $proceeds - $disposalCost;
            $gainLoss = $netProceeds - $nbv;

            $disposal = FaDisposal::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'cost_acquisition' => $costAcq,
                'accum_depreciation' => $accumDep,
                'accum_impairment' => $accumImp,
                'net_book_value' => $nbv,
                'net_proceeds' => $netProceeds,
                'gain_loss' => $gainLoss,
                'requested_by' => $userId,
                'status' => FaDisposal::STATUS_PENDING,
            ]));

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_DISPOSED,
                "Disposal requested via {$data['disposal_method']}",
                ['net_book_value' => $nbv],
                ['proceeds_amount' => $proceeds, 'gain_loss' => $gainLoss],
                $userId,
                $disposal->id,
                FaDisposal::class
            );

            return $disposal;
        });
    }

    public function approveDisposal(FaDisposal $disposal, int $userId): FaDisposal
    {
        return DB::transaction(function () use ($disposal, $userId) {
            $glService = app(AssetGlService::class);
            $je = $glService->postDisposal($disposal, $userId);

            $disposal->update([
                'status' => FaDisposal::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            $asset = $disposal->asset;

            $asset->update([
                'status' => FaAsset::STATUS_DISPOSED,
                'disposal_date' => $disposal->disposal_date,
                'is_active' => false,
            ]);

            if ($je) {
                $disposal->fresh()->update(['journal_entry_id' => $je->id]);
            }

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_DISPOSED,
                "Disposal approved — gain/loss: {$disposal->gain_loss}",
                null,
                ['status' => FaAsset::STATUS_DISPOSED],
                $userId,
                $disposal->id,
                FaDisposal::class
            );

            return $disposal->fresh();
        });
    }

    public function rejectDisposal(FaDisposal $disposal, int $userId): FaDisposal
    {
        $disposal->update([
            'status' => FaDisposal::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $disposal->fresh();
    }

    // ── Impairment ────────────────────────────────

    public function createImpairment(FaAsset $asset, array $data, int $userId): FaImpairment
    {
        $carryingValue = (float) $asset->net_book_value;
        $recoverableAmount = (float) $data['recoverable_amount'];
        $impairmentLoss = max(0, $carryingValue - $recoverableAmount);

        return DB::transaction(function () use ($asset, $data, $userId, $carryingValue, $recoverableAmount, $impairmentLoss) {
            $impairment = FaImpairment::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'carrying_value' => $carryingValue,
                'recoverable_amount' => $recoverableAmount,
                'impairment_loss' => $impairmentLoss,
                'requested_by' => $userId,
                'status' => FaImpairment::STATUS_PENDING,
            ]));

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_IMPAIRED,
                "Impairment loss of {$impairmentLoss} recorded",
                ['net_book_value' => $carryingValue],
                ['recoverable_amount' => $recoverableAmount, 'impairment_loss' => $impairmentLoss],
                $userId,
                $impairment->id,
                FaImpairment::class
            );

            return $impairment;
        });
    }

    public function approveImpairment(FaImpairment $impairment, int $userId): FaImpairment
    {
        return DB::transaction(function () use ($impairment, $userId) {
            $asset = $impairment->asset;
            $loss = (float) $impairment->impairment_loss;

            $glService = app(AssetGlService::class);
            $je = $glService->postImpairment($impairment, $userId);

            $asset->increment('accumulated_impairment', $loss);
            $asset->decrement('net_book_value', $loss);

            $impairment->update([
                'status' => FaImpairment::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            if ($je) {
                $impairment->fresh()->update(['journal_entry_id' => $je->id]);
            }

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_IMPAIRED,
                "Impairment approved — loss: {$loss}",
                null,
                ['accumulated_impairment' => (float) $asset->fresh()->accumulated_impairment],
                $userId,
                $impairment->id,
                FaImpairment::class
            );

            return $impairment->fresh();
        });
    }

    public function approveImpairmentReversal(FaImpairment $impairment, int $userId): FaImpairment
    {
        if (!$impairment->is_reversal) {
            throw new \LogicException('This is not an impairment reversal.');
        }

        return DB::transaction(function () use ($impairment, $userId) {
            $asset = $impairment->asset;
            $reversalAmount = (float) $impairment->reversal_amount;

            $asset->decrement('accumulated_impairment', $reversalAmount);
            $asset->increment('net_book_value', $reversalAmount);

            $impairment->update([
                'status' => FaImpairment::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_IMPAIRED,
                "Impairment reversal approved — amount: {$reversalAmount}",
                null,
                ['accumulated_impairment' => (float) $asset->fresh()->accumulated_impairment],
                $userId,
                $impairment->id,
                FaImpairment::class
            );

            return $impairment->fresh();
        });
    }

    // ── Revaluation ───────────────────────────────

    public function createRevaluation(FaAsset $asset, array $data, int $userId): FaRevaluation
    {
        $previousValue = (float) $asset->net_book_value;
        $newValue = (float) $data['new_value'];
        $surplus = $newValue - $previousValue;

        return DB::transaction(function () use ($asset, $data, $userId, $previousValue, $newValue, $surplus) {
            $revaluation = FaRevaluation::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'previous_value' => $previousValue,
                'new_value' => $newValue,
                'surplus_amount' => $surplus,
                'requested_by' => $userId,
                'status' => FaRevaluation::STATUS_PENDING,
            ]));

            $asset->update(['is_revalued' => true]);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_REVALUED,
                "Revaluation from {$previousValue} to {$newValue} (surplus: {$surplus})",
                ['net_book_value' => $previousValue],
                ['new_value' => $newValue, 'surplus_amount' => $surplus],
                $userId,
                $revaluation->id,
                FaRevaluation::class
            );

            return $revaluation;
        });
    }

    public function approveRevaluation(FaRevaluation $revaluation, int $userId): FaRevaluation
    {
        return DB::transaction(function () use ($revaluation, $userId) {
            $asset = $revaluation->asset;
            $newValue = (float) $revaluation->new_value;

            $glService = app(AssetGlService::class);
            $je = $glService->postRevaluation($revaluation, $userId);

            $asset->update(['net_book_value' => $newValue]);

            $revaluation->update([
                'status' => FaRevaluation::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            if ($je) {
                $revaluation->fresh()->update(['journal_entry_id' => $je->id]);
            }

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_REVALUED,
                "Revaluation approved — new value: {$newValue}",
                null,
                ['net_book_value' => $newValue],
                $userId,
                $revaluation->id,
                FaRevaluation::class
            );

            return $revaluation->fresh();
        });
    }

    public function rejectRevaluation(FaRevaluation $revaluation, int $userId): FaRevaluation
    {
        $asset = $revaluation->asset;
        $hasApprovedRevals = $asset->revaluations()
            ->where('id', '!=', $revaluation->id)
            ->where('status', FaRevaluation::STATUS_APPROVED)
            ->exists();

        $asset->update(['is_revalued' => $hasApprovedRevals]);

        $revaluation->update([
            'status' => FaRevaluation::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $revaluation->fresh();
    }

    // ── Approval Dispatch ─────────────────────────

    public function approve(string $type, $entity, int $userId)
    {
        return match ($type) {
            'disposal' => $this->approveDisposal($entity, $userId),
            'impairment' => $this->approveImpairment($entity, $userId),
            'impairment_reversal' => $this->approveImpairmentReversal($entity, $userId),
            'revaluation' => $this->approveRevaluation($entity, $userId),
            default => throw new \InvalidArgumentException("Unknown approval type: {$type}"),
        };
    }

    public function reject(string $type, $entity, int $userId)
    {
        return match ($type) {
            'disposal' => $this->rejectDisposal($entity, $userId),
            'revaluation' => $this->rejectRevaluation($entity, $userId),
            default => throw new \InvalidArgumentException("Unknown rejection type: {$type}"),
        };
    }
}
