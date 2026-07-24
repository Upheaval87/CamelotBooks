<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationBook;
use App\Models\AssetDisposal;
use App\Models\AssetImpairment;
use App\Models\AssetRevaluation;
use App\Models\AssetTransfer;
use App\Models\DepreciationScheduleEntry;
use App\Models\UnitsOfProductionUsageEntry;
use App\Services\Accounting\FixedAssets\DepreciationEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FixedAssetService
{
    protected JournalPostingEngine $postingEngine;
    protected DepreciationEngine $depreciationEngine;

    public function __construct(JournalPostingEngine $postingEngine, DepreciationEngine $depreciationEngine)
    {
        $this->postingEngine = $postingEngine;
        $this->depreciationEngine = $depreciationEngine;
    }

    public function createAsset(array $data, int $userId): Asset
    {
        $companyId = $data['company_id'];

        $category = AssetCategory::where('id', $data['category_id'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            throw new InvalidArgumentException("Asset category ID {$data['category_id']} not found or inactive for this company.");
        }

        $assetCode = $data['asset_code'] ?? $this->generateAssetCode($companyId);

        return DB::transaction(function () use ($data, $userId, $companyId, $category, $assetCode) {
            $asset = Asset::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'category_id' => $data['category_id'],
                'asset_code' => $assetCode,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'acquisition_date' => $data['acquisition_date'],
                'in_service_date' => $data['in_service_date'] ?? null,
                'acquisition_cost' => $data['acquisition_cost'],
                'residual_value' => $data['residual_value'] ?? $category->residual_value_financial,
                'useful_life' => $data['useful_life'] ?? $category->useful_life_financial,
                'depreciation_method_financial' => $data['depreciation_method_financial'] ?? $category->depreciation_method_financial,
                'depreciation_method_tax' => $data['depreciation_method_tax'] ?? $category->depreciation_method_tax,
                'useful_life_tax' => $data['useful_life_tax'] ?? $category->useful_life_tax,
                'residual_value_tax' => $data['residual_value_tax'] ?? $category->residual_value_tax,
                'depreciation_rate_tax' => $data['depreciation_rate_tax'] ?? $category->depreciation_rate_tax,
                'is_revaluation_enabled' => $data['is_revaluation_enabled'] ?? $category->is_revaluation_enabled,
                'status' => Asset::STATUS_DRAFT,
                'is_active' => true,
                'asset_account_id' => $data['asset_account_id'] ?? $category->asset_account_id,
                'accumulated_depreciation_account_id' => $data['accumulated_depreciation_account_id'] ?? $category->accumulated_depreciation_account_id,
                'depreciation_expense_account_id' => $data['depreciation_expense_account_id'] ?? $category->depreciation_expense_account_id,
                'accumulated_impairment_account_id' => $data['accumulated_impairment_account_id'] ?? $category->accumulated_impairment_account_id,
                'impairment_loss_account_id' => $data['impairment_loss_account_id'] ?? $category->impairment_loss_account_id,
                'disposal_gain_loss_account_id' => $data['disposal_gain_loss_account_id'] ?? $category->disposal_gain_loss_account_id,
                'revaluation_surplus_account_id' => $data['revaluation_surplus_account_id'] ?? $category->revaluation_surplus_account_id,
                'acquisition_source_type' => $data['acquisition_source_type'] ?? null,
                'acquisition_source_id' => $data['acquisition_source_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'created_by' => $userId,
            ]);

            $acquisitionCost = (float) $asset->acquisition_cost;

            AssetDepreciationBook::create([
                'asset_id' => $asset->id,
                'book_type' => AssetDepreciationBook::BOOK_FINANCIAL,
                'depreciation_method' => $asset->depreciation_method_financial,
                'useful_life' => $asset->useful_life,
                'residual_value_type' => 'amount',
                'residual_value' => $asset->residual_value,
                'depreciation_rate' => null,
                'total_estimated_units' => null,
                'sum_of_years_digits' => null,
                'current_cost' => $acquisitionCost,
                'accumulated_depreciation' => 0,
                'accumulated_impairment' => 0,
                'net_book_value' => $acquisitionCost,
                'last_depreciation_date' => null,
                'status' => 'active',
            ]);

            AssetDepreciationBook::create([
                'asset_id' => $asset->id,
                'book_type' => AssetDepreciationBook::BOOK_TAX,
                'depreciation_method' => $asset->depreciation_method_tax,
                'useful_life' => $asset->useful_life_tax,
                'residual_value_type' => 'amount',
                'residual_value' => $asset->residual_value_tax,
                'depreciation_rate' => $asset->depreciation_rate_tax,
                'total_estimated_units' => null,
                'sum_of_years_digits' => null,
                'current_cost' => $acquisitionCost,
                'accumulated_depreciation' => 0,
                'accumulated_impairment' => 0,
                'net_book_value' => $acquisitionCost,
                'last_depreciation_date' => null,
                'status' => 'active',
            ]);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => Asset::class,
                'journalable_id' => $asset->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $asset->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $asset->fresh();
        });
    }

    public function updateAsset(Asset $asset, array $data): Asset
    {
        if ($asset->status !== Asset::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft assets can be updated.');
        }

        if ($asset->company_id !== ($data['company_id'] ?? 0)) {
            throw new InvalidArgumentException('Asset does not belong to this company.');
        }

        return DB::transaction(function () use ($asset, $data) {
            $oldValues = $asset->toArray();

            $asset->update([
                'branch_id' => $data['branch_id'] ?? $asset->branch_id,
                'cost_center_id' => $data['cost_center_id'] ?? $asset->cost_center_id,
                'category_id' => $data['category_id'] ?? $asset->category_id,
                'name' => $data['name'] ?? $asset->name,
                'description' => $data['description'] ?? $asset->description,
                'serial_number' => $data['serial_number'] ?? $asset->serial_number,
                'acquisition_date' => $data['acquisition_date'] ?? $asset->acquisition_date,
                'in_service_date' => $data['in_service_date'] ?? $asset->in_service_date,
                'acquisition_cost' => $data['acquisition_cost'] ?? $asset->acquisition_cost,
                'residual_value' => $data['residual_value'] ?? $asset->residual_value,
                'useful_life' => $data['useful_life'] ?? $asset->useful_life,
                'depreciation_method_financial' => $data['depreciation_method_financial'] ?? $asset->depreciation_method_financial,
                'depreciation_method_tax' => $data['depreciation_method_tax'] ?? $asset->depreciation_method_tax,
                'useful_life_tax' => $data['useful_life_tax'] ?? $asset->useful_life_tax,
                'residual_value_tax' => $data['residual_value_tax'] ?? $asset->residual_value_tax,
                'depreciation_rate_tax' => $data['depreciation_rate_tax'] ?? $asset->depreciation_rate_tax,
                'is_revaluation_enabled' => $data['is_revaluation_enabled'] ?? $asset->is_revaluation_enabled,
                'asset_account_id' => $data['asset_account_id'] ?? $asset->asset_account_id,
                'accumulated_depreciation_account_id' => $data['accumulated_depreciation_account_id'] ?? $asset->accumulated_depreciation_account_id,
                'depreciation_expense_account_id' => $data['depreciation_expense_account_id'] ?? $asset->depreciation_expense_account_id,
                'accumulated_impairment_account_id' => $data['accumulated_impairment_account_id'] ?? $asset->accumulated_impairment_account_id,
                'impairment_loss_account_id' => $data['impairment_loss_account_id'] ?? $asset->impairment_loss_account_id,
                'disposal_gain_loss_account_id' => $data['disposal_gain_loss_account_id'] ?? $asset->disposal_gain_loss_account_id,
                'revaluation_surplus_account_id' => $data['revaluation_surplus_account_id'] ?? $asset->revaluation_surplus_account_id,
                'vendor_id' => $data['vendor_id'] ?? $asset->vendor_id,
            ]);

            $bookChanged = isset($data['depreciation_method_financial'])
                || isset($data['useful_life'])
                || isset($data['residual_value']);

            if ($bookChanged) {
                $financialBook = $asset->depreciationBooks()
                    ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
                    ->first();

                if ($financialBook && $financialBook->accumulated_depreciation == 0) {
                    $financialBook->update([
                        'depreciation_method' => $asset->depreciation_method_financial,
                        'useful_life' => $asset->useful_life,
                        'residual_value' => $asset->residual_value,
                    ]);
                }
            }

            $taxBookChanged = isset($data['depreciation_method_tax'])
                || isset($data['useful_life_tax'])
                || isset($data['residual_value_tax']);

            if ($taxBookChanged) {
                $taxBook = $asset->depreciationBooks()
                    ->where('book_type', AssetDepreciationBook::BOOK_TAX)
                    ->first();

                if ($taxBook && $taxBook->accumulated_depreciation == 0) {
                    $taxBook->update([
                        'depreciation_method' => $asset->depreciation_method_tax,
                        'useful_life' => $asset->useful_life_tax,
                        'residual_value' => $asset->residual_value_tax,
                    ]);
                }
            }

            return $asset->fresh();
        });
    }

    public function activateAsset(Asset $asset, int $userId): Asset
    {
        if ($asset->status !== Asset::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft assets can be activated.');
        }

        return DB::transaction(function () use ($asset, $userId) {
            $oldValues = ['status' => $asset->status];

            $asset->update([
                'status' => Asset::STATUS_ACTIVE,
                'in_service_date' => $asset->in_service_date ?? now()->toDateString(),
            ]);

            $books = $asset->depreciationBooks()->get();

            foreach ($books as $book) {
                $this->depreciationEngine->projectSchedule($asset, $book);
            }

            AccountAuditLog::create([
                'company_id' => $asset->company_id,
                'journalable_type' => Asset::class,
                'journalable_id' => $asset->id,
                'action' => 'activated',
                'old_values' => $oldValues,
                'new_values' => ['status' => Asset::STATUS_ACTIVE],
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $asset->fresh();
        });
    }

    public function createDisposal(array $data, int $userId): AssetDisposal
    {
        $companyId = $data['company_id'];

        $asset = Asset::where('id', $data['asset_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) {
            throw new InvalidArgumentException("Asset ID {$data['asset_id']} not found for this company.");
        }

        if (!in_array($asset->status, [Asset::STATUS_ACTIVE, Asset::STATUS_FULLY_DEPRECIATED])) {
            throw new InvalidArgumentException('Only active or fully depreciated assets can be disposed.');
        }

        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        if (!$financialBook) {
            throw new InvalidArgumentException('Financial depreciation book not found for this asset.');
        }

        $this->validateAccount($companyId, $data['proceeds_account_id']);

        return DB::transaction(function () use ($asset, $data, $userId, $companyId, $financialBook) {
            $disposalDate = $data['disposal_date'];
            $proceedsAmount = (float) ($data['proceeds_amount'] ?? 0);

            $trueUpCharge = $this->depreciationEngine->trueUpToDisposal($asset, $disposalDate);

            $financialBook->refresh();

            $carryingValue = (float) $financialBook->net_book_value;
            $gainLoss = $proceedsAmount - $carryingValue;

            $impairmentAccount = $this->resolveAccount($asset, 'accumulated_impairment_account_id');
            $accumulatedImpairment = (float) $financialBook->accumulated_impairment;

            $jeLines = [];

            $accumulatedDepreciationAccount = $this->resolveAccount($asset, 'accumulated_depreciation_account_id');
            $jeLines[] = [
                'account_id' => $accumulatedDepreciationAccount->id,
                'debit' => (float) $financialBook->accumulated_depreciation,
                'credit' => 0,
                'memo' => "Disposal of {$asset->asset_code} - remove accumulated depreciation",
                'entity_type' => Asset::class,
                'entity_id' => $asset->id,
                'branch_id' => $asset->branch_id,
                'cost_center_id' => $asset->cost_center_id,
            ];

            if ($accumulatedImpairment > 0) {
                $jeLines[] = [
                    'account_id' => $impairmentAccount->id,
                    'debit' => $accumulatedImpairment,
                    'credit' => 0,
                    'memo' => "Disposal of {$asset->asset_code} - remove accumulated impairment",
                    'entity_type' => Asset::class,
                    'entity_id' => $asset->id,
                    'branch_id' => $asset->branch_id,
                    'cost_center_id' => $asset->cost_center_id,
                ];
            }

            if ($proceedsAmount > 0) {
                $proceedsAccount = Account::find($data['proceeds_account_id']);
                $jeLines[] = [
                    'account_id' => $proceedsAccount->id,
                    'debit' => $proceedsAmount,
                    'credit' => 0,
                    'memo' => "Disposal of {$asset->asset_code} - proceeds",
                    'entity_type' => Asset::class,
                    'entity_id' => $asset->id,
                    'branch_id' => $asset->branch_id,
                    'cost_center_id' => $asset->cost_center_id,
                ];
            }

            $assetAccount = $this->resolveAccount($asset, 'asset_account_id');
            $jeLines[] = [
                'account_id' => $assetAccount->id,
                'debit' => 0,
                'credit' => (float) $asset->acquisition_cost,
                'memo' => "Disposal of {$asset->asset_code} - remove asset",
                'entity_type' => Asset::class,
                'entity_id' => $asset->id,
                'branch_id' => $asset->branch_id,
                'cost_center_id' => $asset->cost_center_id,
            ];

            if (abs($gainLoss) > 0.005) {
                $gainLossAccount = $this->resolveAccount($asset, 'disposal_gain_loss_account_id');
                if ($gainLoss > 0) {
                    $jeLines[] = [
                        'account_id' => $gainLossAccount->id,
                        'debit' => 0,
                        'credit' => $gainLoss,
                        'memo' => "Disposal of {$asset->asset_code} - gain on disposal",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ];
                } else {
                    $jeLines[] = [
                        'account_id' => $gainLossAccount->id,
                        'debit' => abs($gainLoss),
                        'credit' => 0,
                        'memo' => "Disposal of {$asset->asset_code} - loss on disposal",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ];
                }
            }

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $disposalDate,
                'source_module' => 'fixed_asset_disposal',
                'reference' => $asset->asset_code,
                'memo' => "Disposal of fixed asset {$asset->asset_code} - {$asset->name}",
                'lines' => $jeLines,
            ]);

            $disposal = AssetDisposal::create([
                'asset_id' => $asset->id,
                'company_id' => $companyId,
                'disposal_date' => $disposalDate,
                'disposal_method' => $data['disposal_method'] ?? 'sale',
                'proceeds_amount' => $proceedsAmount,
                'proceeds_account_id' => $data['proceeds_account_id'],
                'gain_loss_amount' => $gainLoss,
                'journal_entry_id' => $journalEntry->id,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            $asset->update(['status' => Asset::STATUS_DISPOSED]);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => AssetDisposal::class,
                'journalable_id' => $disposal->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $disposal->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $disposal;
        });
    }

    public function createTransfer(array $data, int $userId): AssetTransfer
    {
        $companyId = $data['company_id'];

        $asset = Asset::where('id', $data['asset_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) {
            throw new InvalidArgumentException("Asset ID {$data['asset_id']} not found for this company.");
        }

        if (!in_array($asset->status, [Asset::STATUS_ACTIVE, Asset::STATUS_UNDER_MAINTENANCE])) {
            throw new InvalidArgumentException('Only active or under maintenance assets can be transferred.');
        }

        return DB::transaction(function () use ($asset, $data, $userId, $companyId) {
            $transfer = AssetTransfer::create([
                'asset_id' => $asset->id,
                'company_id' => $companyId,
                'transfer_date' => $data['transfer_date'],
                'from_branch_id' => $asset->branch_id,
                'to_branch_id' => $data['to_branch_id'],
                'from_cost_center_id' => $asset->cost_center_id,
                'to_cost_center_id' => $data['to_cost_center_id'] ?? $asset->cost_center_id,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            $asset->update([
                'branch_id' => $data['to_branch_id'],
                'cost_center_id' => $data['to_cost_center_id'] ?? $asset->cost_center_id,
            ]);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => AssetTransfer::class,
                'journalable_id' => $transfer->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $transfer->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $transfer;
        });
    }

    public function createImpairment(array $data, int $userId): AssetImpairment
    {
        $companyId = $data['company_id'];

        $asset = Asset::where('id', $data['asset_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) {
            throw new InvalidArgumentException("Asset ID {$data['asset_id']} not found for this company.");
        }

        if ($asset->status !== Asset::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Only active assets can be impaired.');
        }

        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        if (!$financialBook) {
            throw new InvalidArgumentException('Financial depreciation book not found for this asset.');
        }

        $recoverableAmount = (float) $data['recoverable_amount'];
        $nbv = (float) $financialBook->net_book_value;

        if ($recoverableAmount >= $nbv) {
            throw new InvalidArgumentException(
                "Recoverable amount ({$recoverableAmount}) must be less than net book value ({$nbv}) for an impairment."
            );
        }

        $impairmentAmount = round($nbv - $recoverableAmount, 2);

        return DB::transaction(function () use ($asset, $data, $userId, $companyId, $financialBook, $recoverableAmount, $nbv, $impairmentAmount) {
            $impairmentLossAccount = $this->resolveAccount($asset, 'impairment_loss_account_id');
            $accumulatedImpairmentAccount = $this->resolveAccount($asset, 'accumulated_impairment_account_id');

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['impairment_date'],
                'source_module' => 'fixed_asset_impairment',
                'reference' => $asset->asset_code,
                'memo' => "Impairment of {$asset->asset_code} - {$asset->name}",
                'lines' => [
                    [
                        'account_id' => $impairmentLossAccount->id,
                        'debit' => $impairmentAmount,
                        'credit' => 0,
                        'memo' => "Impairment loss on {$asset->asset_code}",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ],
                    [
                        'account_id' => $accumulatedImpairmentAccount->id,
                        'debit' => 0,
                        'credit' => $impairmentAmount,
                        'memo' => "Accumulated impairment on {$asset->asset_code}",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ],
                ],
            ]);

            $impairment = AssetImpairment::create([
                'asset_id' => $asset->id,
                'company_id' => $companyId,
                'impairment_date' => $data['impairment_date'],
                'recoverable_amount' => $recoverableAmount,
                'previous_nbv' => $nbv,
                'impairment_amount' => $impairmentAmount,
                'is_reversal' => false,
                'reversed_impairment_id' => null,
                'journal_entry_id' => $journalEntry->id,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            $financialBook->increment('accumulated_impairment', $impairmentAmount);
            $financialBook->decrement('net_book_value', $impairmentAmount);

            $this->depreciationEngine->recomputeScheduleAfterEvent($asset, $financialBook);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => AssetImpairment::class,
                'journalable_id' => $impairment->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $impairment->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $impairment;
        });
    }

    public function reverseImpairment(array $data, int $userId): AssetImpairment
    {
        $companyId = $data['company_id'];

        $asset = Asset::where('id', $data['asset_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) {
            throw new InvalidArgumentException("Asset ID {$data['asset_id']} not found for this company.");
        }

        if ($asset->status !== Asset::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Only active assets can have impairments reversed.');
        }

        $originalImpairment = AssetImpairment::where('id', $data['original_impairment_id'])
            ->where('asset_id', $asset->id)
            ->where('is_reversal', false)
            ->first();

        if (!$originalImpairment) {
            throw new InvalidArgumentException("Original impairment ID {$data['original_impairment_id']} not found for this asset.");
        }

        $alreadyReversed = AssetImpairment::where('reversed_impairment_id', $originalImpairment->id)
            ->where('is_reversal', true)
            ->exists();

        if ($alreadyReversed) {
            throw new InvalidArgumentException('This impairment has already been reversed.');
        }

        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        if (!$financialBook) {
            throw new InvalidArgumentException('Financial depreciation book not found for this asset.');
        }

        $currentAccumulatedImpairment = (float) $financialBook->accumulated_impairment;
        $originalImpairmentAmount = (float) $originalImpairment->impairment_amount;

        $depreciableAmount = (float) $asset->acquisition_cost - (float) $asset->residual_value;
        $currentAccumulatedDepreciation = (float) $financialBook->accumulated_depreciation;
        $carryingValueWithoutImpairment = $depreciableAmount - $currentAccumulatedDepreciation;
        $carryingValueWithImpairment = (float) $financialBook->net_book_value;
        $maxReversal = round($carryingValueWithoutImpairment - $carryingValueWithImpairment, 2);

        $reversalAmount = min($originalImpairmentAmount, $maxReversal);

        if ($reversalAmount <= 0) {
            throw new InvalidArgumentException('No impairment can be reversed at this time. Carrying value would exceed what it would have been without impairment.');
        }

        return DB::transaction(function () use ($asset, $data, $userId, $companyId, $financialBook, $originalImpairment, $reversalAmount) {
            $impairmentLossAccount = $this->resolveAccount($asset, 'impairment_loss_account_id');
            $accumulatedImpairmentAccount = $this->resolveAccount($asset, 'accumulated_impairment_account_id');

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['reversal_date'],
                'source_module' => 'fixed_asset_impairment_reversal',
                'reference' => $asset->asset_code,
                'memo' => "Reversal of impairment for {$asset->asset_code} - {$asset->name}",
                'lines' => [
                    [
                        'account_id' => $accumulatedImpairmentAccount->id,
                        'debit' => $reversalAmount,
                        'credit' => 0,
                        'memo' => "Reversal of accumulated impairment on {$asset->asset_code}",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ],
                    [
                        'account_id' => $impairmentLossAccount->id,
                        'debit' => 0,
                        'credit' => $reversalAmount,
                        'memo' => "Reversal of impairment loss on {$asset->asset_code}",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ],
                ],
            ]);

            $reversal = AssetImpairment::create([
                'asset_id' => $asset->id,
                'company_id' => $companyId,
                'impairment_date' => $data['reversal_date'],
                'recoverable_amount' => (float) $financialBook->net_book_value + $reversalAmount,
                'previous_nbv' => (float) $financialBook->net_book_value,
                'impairment_amount' => $reversalAmount,
                'is_reversal' => true,
                'reversed_impairment_id' => $originalImpairment->id,
                'journal_entry_id' => $journalEntry->id,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            $financialBook->decrement('accumulated_impairment', $reversalAmount);
            $financialBook->increment('net_book_value', $reversalAmount);

            $this->depreciationEngine->recomputeScheduleAfterEvent($asset, $financialBook);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => AssetImpairment::class,
                'journalable_id' => $reversal->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $reversal->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $reversal;
        });
    }

    public function createRevaluation(array $data, int $userId): AssetRevaluation
    {
        $companyId = $data['company_id'];

        $asset = Asset::where('id', $data['asset_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) {
            throw new InvalidArgumentException("Asset ID {$data['asset_id']} not found for this company.");
        }

        if ($asset->status !== Asset::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Only active assets can be revalued.');
        }

        if (!$asset->is_revaluation_enabled) {
            throw new InvalidArgumentException('Revaluation is not enabled for this asset.');
        }

        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->first();

        if (!$financialBook) {
            throw new InvalidArgumentException('Financial depreciation book not found for this asset.');
        }

        $fairValue = (float) $data['fair_value'];
        $previousNbv = (float) $financialBook->net_book_value;
        $surplus = round($fairValue - $previousNbv, 2);

        if ($surplus <= 0) {
            throw new InvalidArgumentException('Fair value must exceed net book value for a revaluation surplus.');
        }

        $revaluationSurplusAccount = $this->resolveAccount($asset, 'revaluation_surplus_account_id');
        $assetAccount = $this->resolveAccount($asset, 'asset_account_id');

        return DB::transaction(function () use ($asset, $data, $userId, $companyId, $financialBook, $fairValue, $previousNbv, $surplus, $revaluationSurplusAccount, $assetAccount) {
            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $data['revaluation_date'],
                'source_module' => 'fixed_asset_revaluation',
                'reference' => $asset->asset_code,
                'memo' => "Revaluation of {$asset->asset_code} - {$asset->name}",
                'lines' => [
                    [
                        'account_id' => $assetAccount->id,
                        'debit' => $surplus,
                        'credit' => 0,
                        'memo' => "Revaluation increase on {$asset->asset_code}",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ],
                    [
                        'account_id' => $revaluationSurplusAccount->id,
                        'debit' => 0,
                        'credit' => $surplus,
                        'memo' => "Revaluation surplus on {$asset->asset_code}",
                        'entity_type' => Asset::class,
                        'entity_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'cost_center_id' => $asset->cost_center_id,
                    ],
                ],
            ]);

            $revaluation = AssetRevaluation::create([
                'asset_id' => $asset->id,
                'company_id' => $companyId,
                'revaluation_date' => $data['revaluation_date'],
                'previous_nbv' => $previousNbv,
                'fair_value' => $fairValue,
                'surplus_amount' => $surplus,
                'existing_surplus_offset' => 0,
                'journal_entry_id' => $journalEntry->id,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            $financialBook->increment('current_cost', $surplus);
            $financialBook->increment('net_book_value', $surplus);

            $this->depreciationEngine->recomputeScheduleAfterEvent($asset, $financialBook);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => AssetRevaluation::class,
                'journalable_id' => $revaluation->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $revaluation->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $revaluation;
        });
    }

    public function logUsage(array $data, int $userId): UnitsOfProductionUsageEntry
    {
        $companyId = $data['company_id'];

        $asset = Asset::where('id', $data['asset_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$asset) {
            throw new InvalidArgumentException("Asset ID {$data['asset_id']} not found for this company.");
        }

        if ($asset->status !== Asset::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Only active assets can have usage logged.');
        }

        $financialBook = $asset->depreciationBooks()
            ->where('book_type', AssetDepreciationBook::BOOK_FINANCIAL)
            ->where('depreciation_method', 'units_of_production')
            ->first();

        if (!$financialBook) {
            throw new InvalidArgumentException('This asset does not use the units of production depreciation method.');
        }

        $unitsUsed = (float) $data['units_used'];
        if ($unitsUsed <= 0) {
            throw new InvalidArgumentException('Units used must be positive.');
        }

        $totalEstimatedUnits = (float) $financialBook->total_estimated_units;
        $previousUsage = (float) UnitsOfProductionUsageEntry::where('asset_id', $asset->id)->sum('units_used');

        if (($previousUsage + $unitsUsed) > $totalEstimatedUnits) {
            throw new InvalidArgumentException(
                "Total usage ({$previousUsage} + {$unitsUsed}) would exceed estimated total units ({$totalEstimatedUnits})."
            );
        }

        return DB::transaction(function () use ($data, $userId, $companyId, $asset, $unitsUsed, $previousUsage) {
            $usage = UnitsOfProductionUsageEntry::create([
                'asset_id' => $asset->id,
                'company_id' => $companyId,
                'period_start_date' => $data['period_start_date'],
                'period_end_date' => $data['period_end_date'],
                'units_used' => $unitsUsed,
                'cumulative_units' => $previousUsage + $unitsUsed,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            AccountAuditLog::create([
                'company_id' => $companyId,
                'journalable_type' => UnitsOfProductionUsageEntry::class,
                'journalable_id' => $usage->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $usage->toArray(),
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $usage;
        });
    }

    public function generateAssetCode(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'ASSET-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastAsset = Asset::where('company_id', $companyId)
            ->where('asset_code', 'like', $prefix . '%')
            ->orderByDesc('asset_code')
            ->first();

        if ($lastAsset) {
            $lastSequence = (int) substr($lastAsset->asset_code, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    public function findAccountByCode(int $companyId, string $code): Account
    {
        $account = Account::where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account with code {$code} not found for this company.");
        }

        return $account;
    }

    protected function resolveAccount(Asset $asset, string $field): Account
    {
        $accountId = $asset->{$field};

        if ($accountId) {
            $account = Account::find($accountId);
            if ($account) {
                return $account;
            }
        }

        $categoryAccountField = str_replace('_account_id', '_account_id', $field);
        if ($asset->category) {
            $categoryAccountId = $asset->category->{$categoryAccountField};
            if ($categoryAccountId) {
                $account = Account::find($categoryAccountId);
                if ($account) {
                    return $account;
                }
            }
        }

        throw new InvalidArgumentException("Account for field {$field} not found on asset or its category.");
    }

    protected function validateAccount(int $companyId, int $accountId): void
    {
        $account = Account::where('id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account ID {$accountId} not found for this company.");
        }
    }
}
