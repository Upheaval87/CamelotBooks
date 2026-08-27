<?php

namespace App\Services\FixedAssets;

use App\Models\FixedAssets\FaAsset;
use App\Models\FixedAssets\FaAcquisition;
use App\Models\FixedAssets\FaCategory;
use App\Models\FixedAssets\FaClass;
use App\Models\FixedAssets\FaCustody;
use App\Models\FixedAssets\FaComponent;
use App\Models\FixedAssets\FaDepBook;
use App\Models\FixedAssets\FaDepMethod;
use App\Models\FixedAssets\FaHistory;
use App\Models\FixedAssets\FaInsurance;
use App\Models\FixedAssets\FaMaintenance;
use App\Models\FixedAssets\FaTransfer;
use App\Models\FixedAssets\FaVerification;
use App\Models\FixedAssets\FaWarranty;
use App\Services\Admin\NumberingSequenceService;
use App\Services\FixedAssets\AssetGlService;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(
        private NumberingSequenceService $numberingService,
    ) {
    }

    // ── Asset CRUD ────────────────────────────────

    public function create(array $data, int $userId): FaAsset
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['asset_code'] = $this->numberingService->getNextNumber(
                $data['company_id'],
                'fixed_asset'
            );
            $data['net_book_value'] = $data['acquisition_cost'];
            $data['created_by'] = $userId;
            $data['status'] = FaAsset::STATUS_DRAFT;

            $asset = FaAsset::create($data);

            FaDepBook::create([
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'book_type' => FaDepBook::BOOK_FINANCIAL,
                'depreciation_method' => $asset->depreciation_method,
                'useful_life' => $asset->useful_life,
                'residual_value' => $asset->residual_value,
                'depreciation_rate' => $asset->depreciation_rate,
                'cost' => $asset->acquisition_cost,
                'accumulated_depreciation' => 0,
                'net_book_value' => $asset->acquisition_cost,
            ]);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_CREATED,
                "Asset {$asset->asset_code} created",
                null,
                $asset->toArray(),
                $userId
            );

            return $asset;
        });
    }

    public function update(FaAsset $asset, array $data): FaAsset
    {
        return DB::transaction(function () use ($asset, $data) {
            $oldValues = $asset->toArray();

            $asset->update($data);

            $this->syncDepBookFinancial($asset);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_ADJUSTMENT,
                "Asset {$asset->asset_code} updated",
                $oldValues,
                $asset->fresh()->toArray(),
            );

            return $asset->fresh();
        });
    }

    public function activate(FaAsset $asset, int $userId): FaAsset
    {
        if ($asset->status !== FaAsset::STATUS_DRAFT) {
            throw new \LogicException('Only draft assets can be activated.');
        }

        return DB::transaction(function () use ($asset, $userId) {
            $oldStatus = $asset->status;

            $asset->update([
                'status' => FaAsset::STATUS_ACTIVE,
                'in_service_date' => $asset->in_service_date ?? now()->toDateString(),
            ]);

            $glService = app(AssetGlService::class);
            $je = $glService->postActivation($asset->fresh(), $userId);

            if ($je) {
                $asset->fresh()->update(['journal_entry_id' => $je->id]);
            }

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_ACTIVATED,
                "Asset {$asset->asset_code} activated",
                ['status' => $oldStatus],
                ['status' => FaAsset::STATUS_ACTIVE],
                $userId
            );

            return $asset->fresh();
        });
    }

    public function dispose(FaAsset $asset): void
    {
        $asset->update([
            'status' => FaAsset::STATUS_DISPOSED,
            'disposal_date' => now()->toDateString(),
            'is_active' => false,
        ]);
    }

    public function scrap(FaAsset $asset): void
    {
        $asset->update([
            'status' => FaAsset::STATUS_SCRAPPED,
            'disposal_date' => now()->toDateString(),
            'is_active' => false,
        ]);
    }

    public function void(FaAsset $asset): void
    {
        if ($asset->accumulated_depreciation > 0) {
            throw new \LogicException('Cannot void an asset with accumulated depreciation.');
        }

        $asset->update([
            'status' => FaAsset::STATUS_DRAFT,
            'is_active' => true,
            'disposal_date' => null,
        ]);
    }

    public function destroy(FaAsset $asset): void
    {
        if ($asset->status !== FaAsset::STATUS_DRAFT) {
            throw new \LogicException('Only draft assets can be deleted.');
        }

        DB::transaction(function () use ($asset) {
            $asset->history()->delete();
            $asset->depBooks()->delete();
            $asset->components()->delete();
            $asset->acquisitions()->delete();
            $asset->transfers()->delete();
            $asset->disposals()->delete();
            $asset->impairments()->delete();
            $asset->revaluations()->delete();
            $asset->maintenanceRecords()->delete();
            $asset->insurancePolicies()->delete();
            $asset->warranties()->delete();
            $asset->verificationLines()->delete();
            $asset->custodyRecords()->delete();
            $asset->documents()->delete();
            $asset->delete();
        });
    }

    // ── Category CRUD ─────────────────────────────

    public function createCategory(array $data): FaCategory
    {
        return FaCategory::create($data);
    }

    public function updateCategory(FaCategory $category, array $data): FaCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function toggleCategory(FaCategory $category): FaCategory
    {
        $category->update(['is_active' => !$category->is_active]);
        return $category->fresh();
    }

    // ── Class CRUD ────────────────────────────────

    public function createClass(array $data): FaClass
    {
        return FaClass::create($data);
    }

    public function updateClass(FaClass $class, array $data): FaClass
    {
        $class->update($data);
        return $class->fresh();
    }

    // ── Depreciation Method CRUD ──────────────────

    public function createDepMethod(array $data): FaDepMethod
    {
        return FaDepMethod::create($data);
    }

    public function updateDepMethod(FaDepMethod $method, array $data): FaDepMethod
    {
        $method->update($data);
        return $method->fresh();
    }

    // ── Component CRUD ────────────────────────────

    public function addComponent(FaAsset $asset, array $data): FaComponent
    {
        return DB::transaction(function () use ($asset, $data) {
            $component = FaComponent::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
            ]));

            $asset->update(['is_componentised' => true]);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_ADJUSTMENT,
                "Component added: {$component->name}",
            );

            return $component;
        });
    }

    public function removeComponent(FaComponent $component): void
    {
        DB::transaction(function () use ($component) {
            $asset = $component->asset;

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_ADJUSTMENT,
                "Component removed: {$component->name}",
            );

            $component->delete();

            if ($asset->components()->count() === 0) {
                $asset->update(['is_componentised' => false]);
            }
        });
    }

    // ── Transfer ──────────────────────────────────

    public function transfer(FaAsset $asset, array $data, int $userId): FaTransfer
    {
        return DB::transaction(function () use ($asset, $data, $userId) {
            $transfer = FaTransfer::create(array_merge($data, [
                'company_id' => $asset->company_id,
                'asset_id' => $asset->id,
                'requested_by' => $userId,
                'status' => FaTransfer::STATUS_PENDING,
            ]));

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_TRANSFERRED,
                "Transfer requested to " . ($data['to_location'] ?? 'new location'),
                null,
                $transfer->toArray(),
                $userId,
                $transfer->id,
                FaTransfer::class
            );

            return $transfer;
        });
    }

    public function approveTransfer(FaTransfer $transfer, int $userId): FaTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $asset = $transfer->asset;

            $oldValues = [
                'branch_id' => $asset->branch_id,
                'cost_center_id' => $asset->cost_center_id,
                'location' => $asset->location,
                'custodian' => $asset->custodian,
            ];

            $asset->update([
                'branch_id' => $transfer->to_branch_id,
                'cost_center_id' => $transfer->to_cost_center_id,
                'location' => $transfer->to_location,
                'custodian' => $transfer->to_custodian,
            ]);

            $transfer->update([
                'status' => FaTransfer::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            FaHistory::log(
                $asset->company_id,
                $asset->id,
                FaHistory::EVENT_TRANSFERRED,
                "Transfer approved",
                $oldValues,
                $asset->fresh()->toArray(),
                $userId,
                $transfer->id,
                FaTransfer::class
            );

            return $transfer->fresh();
        });
    }

    public function rejectTransfer(FaTransfer $transfer, int $userId): FaTransfer
    {
        $transfer->update([
            'status' => FaTransfer::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $transfer->fresh();
    }

    // ── Helpers ───────────────────────────────────

    // ── Maintenance CRUD ─────────────────────────

    public function createMaintenance(FaAsset $asset, array $data, int $userId): FaMaintenance
    {
        $record = FaMaintenance::create(array_merge($data, [
            'company_id' => $asset->company_id,
            'asset_id' => $asset->id,
            'requested_by' => $userId,
        ]));

        FaHistory::log(
            $asset->company_id, $asset->id, FaHistory::EVENT_ADJUSTMENT,
            "Maintenance recorded: {$record->type_label}", null, $record->toArray(), $userId
        );

        return $record;
    }

    public function updateMaintenance(FaMaintenance $record, array $data): FaMaintenance
    {
        $record->update($data);
        return $record->fresh();
    }

    // ── Insurance CRUD ───────────────────────────

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

    // ── Warranty CRUD ────────────────────────────

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

    // ── Custody CRUD ─────────────────────────────

    public function createCustody(FaAsset $asset, array $data, int $userId): FaCustody
    {
        return FaCustody::create(array_merge($data, [
            'company_id' => $asset->company_id,
            'asset_id' => $asset->id,
            'handed_by' => $userId,
        ]));
    }

    // ── Verification CRUD ────────────────────────

    public function createVerification(array $data, int $companyId): FaVerification
    {
        return FaVerification::create(array_merge($data, [
            'company_id' => $companyId,
            'total_assets' => 0,
            'verified_count' => 0,
            'variance_count' => 0,
            'status' => FaVerification::STATUS_PENDING,
        ]));
    }

    public function updateVerification(FaVerification $verification, array $data): FaVerification
    {
        $verification->update($data);
        return $verification->fresh();
    }

    private function syncDepBookFinancial(FaAsset $asset): void
    {
        $book = $asset->financialBook;

        if ($book) {
            $book->update([
                'depreciation_method' => $asset->depreciation_method,
                'useful_life' => $asset->useful_life,
                'residual_value' => $asset->residual_value,
                'depreciation_rate' => $asset->depreciation_rate,
            ]);
        }
    }
}
