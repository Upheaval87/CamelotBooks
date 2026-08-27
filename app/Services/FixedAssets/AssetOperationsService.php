<?php

namespace App\Services\FixedAssets;

use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaMaintenance;
use App\Models\FixedAssets\FaInsurance;
use App\Models\FixedAssets\FaWarranty;
use App\Models\FixedAssets\FaCustody;
use App\Models\FixedAssets\FaVerification;
use App\Models\FixedAssets\FaVerificationLine;
use App\Models\FixedAssets\FaHistory;
use Illuminate\Support\Facades\DB;

class AssetOperationsService
{
    // ── Maintenance ───────────────────────────────

    public function createMaintenance(FaAsset $asset, array $data, ?int $userId = null): FaMaintenance
    {
        return DB::transaction(function () use ($asset, $data, $userId) {
            $record = FaMaintenance::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'requested_by' => $userId,
            ]));

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_MAINTENANCE,
                "Maintenance recorded: {$record->description}",
                null,
                $record->toArray(),
                $userId,
                $record->id,
                FaMaintenance::class
            );

            return $record;
        });
    }

    public function updateMaintenance(FaMaintenance $record, array $data): FaMaintenance
    {
        $record->update($data);
        return $record->fresh();
    }

    // ── Insurance ─────────────────────────────────

    public function createInsurance(FaAsset $asset, array $data): FaInsurance
    {
        return FaInsurance::create(array_merge($data, [
            'company_id' => $asset->company_id,
            'asset_id' => $asset->id,
            'status' => FaInsurance::STATUS_ACTIVE,
        ]));
    }

    public function updateInsurance(FaInsurance $policy, array $data): FaInsurance
    {
        $policy->update($data);
        return $policy->fresh();
    }

    // ── Warranty ──────────────────────────────────

    public function createWarranty(FaAsset $asset, array $data): FaWarranty
    {
        return FaWarranty::create(array_merge($data, [
            'company_id' => $asset->company_id,
            'asset_id' => $asset->id,
            'status' => FaWarranty::STATUS_ACTIVE,
        ]));
    }

    public function updateWarranty(FaWarranty $warranty, array $data): FaWarranty
    {
        $warranty->update($data);
        return $warranty->fresh();
    }

    // ── Custody ───────────────────────────────────

    public function assignCustody(FaAsset $asset, array $data, int $userId): FaCustody
    {
        return DB::transaction(function () use ($asset, $data, $userId) {
            $custody = FaCustody::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'handed_by' => $userId,
            ]));

            $asset->update([
                'custodian' => $data['to_custodian'] ?? $asset->custodian,
                'location' => $data['actual_location'] ?? $asset->location,
            ]);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_CUSTODY,
                "Custody handed to " . ($data['to_custodian'] ?? 'unknown'),
                null,
                $custody->toArray(),
                $userId,
                $custody->id,
                FaCustody::class
            );

            return $custody;
        });
    }

    public function returnCustody(FaCustody $custody, int $receivedByUserId): FaCustody
    {
        $custody->update([
            'received_by' => $receivedByUserId,
        ]);

        FaHistory::log(
            $custody->company_id,
            $custody->asset_id,
            FaHistory::EVENT_CUSTODY,
            "Custody returned from {$custody->to_custodian}",
            null,
            ['received_by' => $receivedByUserId],
            $receivedByUserId,
            $custody->id,
            FaCustody::class
        );

        return $custody->fresh();
    }

    // ── Verification ──────────────────────────────

    public function createVerification(int $companyId, array $data, int $userId): FaVerification
    {
        return FaVerification::create(array_merge($data, [
            'company_id' => $companyId,
            'assigned_to' => $userId,
            'status' => FaVerification::STATUS_PENDING,
        ]));
    }

    public function addVerificationLine(FaVerification $verification, FaAsset $asset, array $data): FaVerificationLine
    {
        return FaVerificationLine::create(array_merge($data, [
            'company_id' => $verification->company_id,
            'verification_id' => $verification->id,
            'asset_id' => $asset->id,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'is_verified' => $data['is_verified'] ?? false,
        ]));
    }

    public function completeVerification(FaVerification $verification): FaVerification
    {
        return DB::transaction(function () use ($verification) {
            $verification->update([
                'status' => FaVerification::STATUS_COMPLETED,
                'completed_date' => now()->toDateString(),
            ]);

            $verifiedCount = $verification->lines()->verified()->count();
            $totalAssets = $verification->lines()->count();
            $varianceCount = $verification->lines()->withVariance()->count();

            $verification->update([
                'verified_count' => $verifiedCount,
                'total_assets' => $totalAssets,
                'variance_count' => $varianceCount,
            ]);

            $verifiedLines = $verification->lines()->verified()->get();
            foreach ($verifiedLines as $line) {
                FaHistory::log(
                    $verification->company_id,
                    $line->asset_id,
                    FaHistory::EVENT_VERIFIED,
                    "Asset verified during verification: {$verification->name}",
                    null,
                    ['condition' => $line->condition, 'actual_location' => $line->actual_location],
                    $line->verified_by,
                    $verification->id,
                    FaVerification::class
                );
            }

            return $verification->fresh();
        });
    }
}
