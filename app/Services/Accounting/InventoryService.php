<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function receiveStock(int $companyId, int $productId, ?int $branchId, float $quantity, float $unitCost, string $sourceType, int $sourceId, string $date): InventoryCostLayer
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Receive quantity must be positive.');
        }

        return DB::transaction(function () use ($companyId, $productId, $branchId, $quantity, $unitCost, $sourceType, $sourceId, $date) {
            $layer = InventoryCostLayer::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity_remaining' => $quantity,
                'unit_cost' => $unitCost,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'date' => $date,
            ]);

            $stock = InventoryStock::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                ],
                ['quantity_on_hand' => 0]
            );

            $stock->increment('quantity_on_hand', $quantity);

            return $layer;
        });
    }

    /**
     * Consume stock using FIFO. Returns array of consumed layers with costs.
     *
     * @return array<int, array{layer_id: int, quantity: float, unit_cost: float, total_cost: float}>
     */
    public function consumeStock(int $companyId, int $productId, ?int $branchId, float $quantity, string $date): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Consume quantity must be positive.');
        }

        $company = Company::findOrFail($companyId);

        if (!$company->allow_negative_stock) {
            $onHand = $this->getQuantityOnHand($companyId, $productId, $branchId);
            if ($onHand < $quantity) {
                throw new InvalidArgumentException(
                    "Insufficient stock. On hand: {$onHand}, requested: {$quantity}."
                );
            }
        }

        return DB::transaction(function () use ($companyId, $productId, $branchId, $quantity, $date) {
            $remainingToConsume = $quantity;
            $consumedLayers = [];

            $layers = InventoryCostLayer::where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->available()
                ->orderBy('date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($layers as $layer) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                $consumeFromLayer = min($layer->quantity_remaining, $remainingToConsume);
                $totalCost = round($consumeFromLayer * $layer->unit_cost, 2);

                $layer->decrement('quantity_remaining', $consumeFromLayer);
                $remainingToConsume -= $consumeFromLayer;

                $consumedLayers[] = [
                    'layer_id' => $layer->id,
                    'quantity' => $consumeFromLayer,
                    'unit_cost' => $layer->unit_cost,
                    'total_cost' => $totalCost,
                ];
            }

            $stock = InventoryStock::where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->first();

            if ($stock) {
                $stock->decrement('quantity_on_hand', $quantity);
            }

            return $consumedLayers;
        });
    }

    public function getQuantityOnHand(int $companyId, int $productId, ?int $branchId = null): float
    {
        $query = InventoryStock::where('company_id', $companyId)
            ->where('product_id', $productId);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('quantity_on_hand');
    }

    public function getProductTotalQuantityOnHand(int $companyId, int $productId): float
    {
        return (float) InventoryStock::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->sum('quantity_on_hand');
    }

    public function getValuation(int $companyId, ?string $asOfDate = null): array
    {
        $query = InventoryCostLayer::where('inventory_cost_layers.company_id', $companyId)
            ->available()
            ->join('products', 'products.id', '=', 'inventory_cost_layers.product_id')
            ->select(
                'inventory_cost_layers.product_id',
                'products.name as product_name',
                'products.sku',
                'inventory_cost_layers.branch_id',
                DB::raw('SUM(inventory_cost_layers.quantity_remaining) as total_quantity'),
                DB::raw('SUM(inventory_cost_layers.quantity_remaining * inventory_cost_layers.unit_cost) as total_value')
            )
            ->groupBy('inventory_cost_layers.product_id', 'products.name', 'products.sku', 'inventory_cost_layers.branch_id');

        if ($asOfDate) {
            $query->where('inventory_cost_layers.date', '<=', $asOfDate);
            $query->whereRaw('inventory_cost_layers.id NOT IN (
                SELECT id FROM inventory_cost_layers
                WHERE company_id = ? AND date > ? AND quantity_remaining < quantity_remaining
            )', [$companyId, $asOfDate]);
        }

        return $query->get()->toArray();
    }

    public function getValuationByProduct(int $companyId): array
    {
        return InventoryCostLayer::where('inventory_cost_layers.company_id', $companyId)
            ->available()
            ->join('products', 'products.id', '=', 'inventory_cost_layers.product_id')
            ->select(
                'inventory_cost_layers.product_id',
                'products.name as product_name',
                'products.sku',
                DB::raw('SUM(inventory_cost_layers.quantity_remaining) as total_quantity'),
                DB::raw('SUM(inventory_cost_layers.quantity_remaining * inventory_cost_layers.unit_cost) as total_value'),
                DB::raw('ROUND(AVG(inventory_cost_layers.unit_cost), 4) as avg_cost')
            )
            ->groupBy('inventory_cost_layers.product_id', 'products.name', 'products.sku')
            ->get()
            ->toArray();
    }

    public function getLowStockItems(int $companyId): array
    {
        return Product::where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->where('is_active', true)
            ->whereNotNull('reorder_point')
            ->where('reorder_point', '>', 0)
            ->with('stock')
            ->get()
            ->filter(function ($product) {
                $onHand = $product->stock->sum('quantity_on_hand');
                return $onHand <= $product->reorder_point;
            })
            ->map(function ($product) {
                $onHand = $product->stock->sum('quantity_on_hand');
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'reorder_point' => $product->reorder_point,
                    'quantity_on_hand' => $onHand,
                    'shortage' => $product->reorder_point - $onHand,
                ];
            })
            ->values()
            ->toArray();
    }

    public function adjustStock(int $companyId, int $productId, ?int $branchId, string $type, float $quantity, string $reasonCode, ?string $memo, ?float $unitCost, int $userId, string $date): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Adjustment quantity must be positive.');
        }

        return DB::transaction(function () use ($companyId, $productId, $branchId, $type, $quantity, $reasonCode, $memo, $unitCost, $userId, $date) {
            $adjustmentNumber = $this->generateAdjustmentNumber($companyId);

            if ($type === 'decrease') {
                if ($unitCost === null) {
                    $consumedLayers = $this->consumeStock($companyId, $productId, $branchId, $quantity, $date);
                    $totalCost = array_sum(array_column($consumedLayers, 'total_cost'));
                    $avgUnitCost = $quantity > 0 ? round($totalCost / $quantity, 4) : 0;
                } else {
                    $avgUnitCost = $unitCost;
                    $totalCost = round($quantity * $unitCost, 2);
                    $this->consumeStock($companyId, $productId, $branchId, $quantity, $date);
                }
            } else {
                if ($unitCost === null) {
                    throw new InvalidArgumentException('Unit cost is required for stock increases.');
                }
                $avgUnitCost = $unitCost;
                $totalCost = round($quantity * $unitCost, 2);
            }

            return [
                'adjustment_number' => $adjustmentNumber,
                'unit_cost' => $avgUnitCost,
                'total_cost' => $totalCost,
            ];
        });
    }

    public function transferStock(int $companyId, int $productId, int $fromBranchId, int $toBranchId, float $quantity, ?string $memo, int $userId, string $date): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be positive.');
        }

        if ($fromBranchId === $toBranchId) {
            throw new InvalidArgumentException('Source and destination branches must be different.');
        }

        return DB::transaction(function () use ($companyId, $productId, $fromBranchId, $toBranchId, $quantity, $memo, $userId, $date) {
            $consumedLayers = $this->consumeStock($companyId, $productId, $fromBranchId, $quantity, $date);

            $totalCost = array_sum(array_column($consumedLayers, 'total_cost'));

            foreach ($consumedLayers as $consumed) {
                $this->receiveStock(
                    $companyId,
                    $productId,
                    $toBranchId,
                    $consumed['quantity'],
                    $consumed['unit_cost'],
                    'transfer',
                    0,
                    $date
                );
            }

            $transferNumber = $this->generateTransferNumber($companyId);

            $transfer = InventoryTransfer::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'transfer_number' => $transferNumber,
                'date' => $date,
                'quantity' => $quantity,
                'memo' => $memo,
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            return [
                'transfer' => $transfer,
                'total_cost' => $totalCost,
            ];
        });
    }

    public function generateAdjustmentNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'ADJ-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = DB::table('inventory_adjustments')
            ->where('company_id', $companyId)
            ->where('adjustment_number', 'like', $prefix . '%')
            ->orderByDesc('adjustment_number')
            ->first();

        if ($last) {
            $lastSequence = (int) substr($last->adjustment_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    public function generateTransferNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'TRF-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = DB::table('inventory_transfers')
            ->where('company_id', $companyId)
            ->where('transfer_number', 'like', $prefix . '%')
            ->orderByDesc('transfer_number')
            ->first();

        if ($last) {
            $lastSequence = (int) substr($last->transfer_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalInventoryAssetValue(int $companyId): float
    {
        return (float) InventoryCostLayer::where('company_id', $companyId)
            ->available()
            ->sum(DB::raw('quantity_remaining * unit_cost'));
    }
}
