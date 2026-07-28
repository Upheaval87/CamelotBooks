<?php

namespace App\Services\Inventory;

use App\Models\Account;
use App\Models\DefaultAccountMapping;
use App\Models\GoodsReceivedNote;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\LandedCostComponent;
use App\Models\LandedCostVoucher;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LandedCostAllocationService
{
    protected JournalPostingEngine $postingEngine;
    protected InventoryService $inventoryService;

    public function __construct(JournalPostingEngine $postingEngine, InventoryService $inventoryService)
    {
        $this->postingEngine = $postingEngine;
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data, int $userId): LandedCostVoucher
    {
        $companyId = $data['company_id'];

        if (empty($data['components'])) {
            throw new InvalidArgumentException('At least one landed cost component is required.');
        }

        if (empty($data['grn_ids'])) {
            throw new InvalidArgumentException('At least one GRN must be linked.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $voucherNumber = $this->generateVoucherNumber($companyId);
            $totalAmount = array_sum(array_column($data['components'], 'amount'));

            $voucher = LandedCostVoucher::create([
                'company_id' => $companyId,
                'voucher_number' => $voucherNumber,
                'vendor_id' => $data['vendor_id'],
                'allocation_method' => $data['allocation_method'] ?? 'by_value',
                'total_amount' => round($totalAmount, 2),
                'status' => LandedCostVoucher::STATUS_DRAFT,
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['components'] as $component) {
                LandedCostComponent::create([
                    'voucher_id' => $voucher->id,
                    'component_type' => $component['component_type'],
                    'description' => $component['description'],
                    'amount' => $component['amount'],
                    'payee_account_id' => $component['payee_account_id'],
                ]);
            }

            $voucher->grns()->sync($data['grn_ids']);

            return $voucher;
        });
    }

    public function post(LandedCostVoucher $voucher, int $userId): LandedCostVoucher
    {
        if ($voucher->status !== LandedCostVoucher::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft vouchers can be posted.');
        }

        $companyId = $voucher->company_id;

        return DB::transaction(function () use ($voucher, $userId, $companyId) {
            $oldValues = $voucher->toArray();

            $voucher->load(['components', 'grns.lines']);

            if ($voucher->grns->isEmpty()) {
                throw new InvalidArgumentException('No GRNs linked to this voucher.');
            }

            $totalLandedCost = $voucher->total_amount;
            $allocation = $this->computeAllocation($voucher, $totalLandedCost, $companyId);

            $journalLines = $this->buildJournalLines($voucher, $allocation, $companyId, $userId);

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $voucher->date->format('Y-m-d'),
                'source_module' => 'landed_cost',
                'reference' => $voucher->voucher_number,
                'memo' => "Landed cost allocation - {$voucher->voucher_number}",
                'lines' => $journalLines,
            ]);

            $this->updateCostLayers($allocation, $companyId);

            $voucher->update([
                'status' => LandedCostVoucher::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
            ]);

            $this->logVoucherAction($voucher, 'posted', $oldValues, $voucher->toArray(), $userId);

            return $voucher;
        });
    }

    protected function computeAllocation(LandedCostVoucher $voucher, float $totalLandedCost, int $companyId): array
    {
        $method = $voucher->allocation_method;

        $allGrnLineTotals = [];
        $allGrnLineQuantities = [];
        $totalValue = 0;
        $totalQuantity = 0;

        foreach ($voucher->grns as $grn) {
            foreach ($grn->lines as $line) {
                $allGrnLineTotals[$line->id] = (float) $line->total_cost;
                $allGrnLineQuantities[$line->id] = (float) $line->quantity_received;
                $totalValue += (float) $line->total_cost;
                $totalQuantity += (float) $line->quantity_received;
            }
        }

        $perUnit = $method === 'by_value'
            ? ($totalValue > 0 ? $totalLandedCost / $totalValue : 0)
            : ($totalQuantity > 0 ? $totalLandedCost / $totalQuantity : 0);

        $allocations = [];

        foreach ($allGrnLineTotals as $grnLineId => $grnTotal) {
            $baseCost = $method === 'by_value' ? $grnTotal : $allGrnLineQuantities[$grnLineId];
            $allocatedAmount = round($baseCost * $perUnit, 2);

            $qtyReceived = $allGrnLineQuantities[$grnLineId];
            $qtyOnHand = $this->getQtyOnHandForGrnLine($grnLineId, $companyId);
            $qtyConsumed = max(0, $qtyReceived - $qtyOnHand);

            $perUnitAdditional = $qtyReceived > 0 ? $allocatedAmount / $qtyReceived : 0;
            $inventoryAdjustment = round($qtyOnHand * $perUnitAdditional, 2);
            $cogsAdjustment = round($qtyConsumed * $perUnitAdditional, 2);

            $grnLine = \App\Models\GrnLine::with('product')->find($grnLineId);

            $allocations[$grnLineId] = [
                'grn_line_id' => $grnLineId,
                'product_id' => $grnLine?->product_id,
                'branch_id' => $grnLine?->goodsReceivedNote?->branch_id,
                'allocated_amount' => $allocatedAmount,
                'qty_received' => $qtyReceived,
                'qty_on_hand' => $qtyOnHand,
                'qty_consumed' => $qtyConsumed,
                'inventory_asset_amount' => $inventoryAdjustment,
                'cogs_amount' => $cogsAdjustment,
                'inventory_asset_account_id' => $grnLine?->product?->inventory_asset_account_id,
            ];
        }

        return $allocations;
    }

    protected function buildJournalLines(LandedCostVoucher $voucher, array $allocation, int $companyId, int $userId): array
    {
        $lines = [];
        $totalDebit = 0;
        $totalCredit = 0;

        $inventoryAssetAccount = DefaultAccountMapping::getAccount($companyId, 'inventory_asset');
        $cogsAccount = DefaultAccountMapping::getAccount($companyId, 'default_expense');

        foreach ($allocation as $alloc) {
            if ($alloc['inventory_asset_amount'] > 0 && $alloc['inventory_asset_account_id']) {
                $account = Account::find($alloc['inventory_asset_account_id']) ?? $inventoryAssetAccount;
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $alloc['inventory_asset_amount'],
                    'credit' => 0,
                    'memo' => "Landed cost - inventory asset",
                ];
                $totalDebit += $alloc['inventory_asset_amount'];
            }

            if ($alloc['cogs_amount'] > 0) {
                $lines[] = [
                    'account_id' => $cogsAccount->id,
                    'debit' => $alloc['cogs_amount'],
                    'credit' => 0,
                    'memo' => "Landed cost - COGS adjustment",
                ];
                $totalDebit += $alloc['cogs_amount'];
            }
        }

        $payeeAccountTotals = [];
        foreach ($voucher->components as $component) {
            $payeeAccountId = $component->payee_account_id;
            $payeeAccountTotals[$payeeAccountId] = ($payeeAccountTotals[$payeeAccountId] ?? 0) + $component->amount;
        }

        foreach ($payeeAccountTotals as $payeeAccountId => $amount) {
            $lines[] = [
                'account_id' => $payeeAccountId,
                'debit' => 0,
                'credit' => round($amount, 2),
                'memo' => "Landed cost - {$voucher->voucher_number}",
            ];
            $totalCredit += round($amount, 2);
        }

        $difference = round($totalDebit - $totalCredit, 2);
        if (abs($difference) > 0.01) {
            if ($difference > 0) {
                $lines[] = [
                    'account_id' => $cogsAccount->id,
                    'debit' => 0,
                    'credit' => abs($difference),
                    'memo' => "Landed cost rounding adjustment",
                ];
            } else {
                $lines[] = [
                    'account_id' => $cogsAccount->id,
                    'debit' => abs($difference),
                    'credit' => 0,
                    'memo' => "Landed cost rounding adjustment",
                ];
            }
        }

        return $lines;
    }

    protected function getQtyOnHandForGrnLine(int $grnLineId, int $companyId): float
    {
        $grnLine = \App\Models\GrnLine::with('goodsReceivedNote')->find($grnLineId);
        if (!$grnLine || !$grnLine->product_id) {
            return 0;
        }

        $query = InventoryStock::where('company_id', $companyId)
            ->where('product_id', $grnLine->product_id);

        if ($grnLine->goodsReceivedNote?->branch_id) {
            $query->where('branch_id', $grnLine->goodsReceivedNote->branch_id);
        }

        return (float) $query->sum('quantity_on_hand');
    }

    protected function updateCostLayers(array $allocation, int $companyId): void
    {
        foreach ($allocation as $alloc) {
            if ($alloc['allocated_amount'] <= 0 || $alloc['qty_on_hand'] <= 0) {
                continue;
            }

            $grnLine = \App\Models\GrnLine::find($alloc['grn_line_id']);
            if (!$grnLine) {
                continue;
            }

            $layers = InventoryCostLayer::where('company_id', $companyId)
                ->where('product_id', $alloc['product_id'])
                ->where('source_type', 'grn')
                ->where('source_id', $alloc['grn_line_id'])
                ->where('quantity_remaining', '>', 0)
                ->orderBy('date')
                ->get();

            $additionalPerUnit = $alloc['qty_on_hand'] > 0
                ? $alloc['inventory_asset_amount'] / $alloc['qty_on_hand']
                : 0;

            foreach ($layers as $layer) {
                if ($additionalPerUnit > 0) {
                    $newUnitCost = $layer->unit_cost + $additionalPerUnit;
                    $layer->update(['unit_cost' => round($newUnitCost, 4)]);
                }
            }
        }
    }

    protected function generateVoucherNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'LC-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastVoucher = LandedCostVoucher::where('company_id', $companyId)
            ->where('voucher_number', 'like', $prefix . '%')
            ->orderByDesc('voucher_number')
            ->first();

        if ($lastVoucher) {
            $lastSequence = (int) substr($lastVoucher->voucher_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function logVoucherAction(LandedCostVoucher $voucher, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        \App\Models\AccountAuditLog::create([
            'company_id' => $voucher->company_id,
            'journalable_type' => LandedCostVoucher::class,
            'journalable_id' => $voucher->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
