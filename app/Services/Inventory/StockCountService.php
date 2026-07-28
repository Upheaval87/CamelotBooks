<?php

namespace App\Services\Inventory;

use App\Models\DefaultAccountMapping;
use App\Models\InventoryCostLayer;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockCountService
{
    protected InventoryService $inventoryService;
    protected JournalPostingEngine $postingEngine;

    public function __construct(InventoryService $inventoryService, JournalPostingEngine $postingEngine)
    {
        $this->inventoryService = $inventoryService;
        $this->postingEngine = $postingEngine;
    }

    public function createCount(array $data, int $userId): StockCount
    {
        $companyId = $data['company_id'];

        $count = StockCount::create([
            'company_id' => $companyId,
            'count_number' => $this->generateCountNumber($companyId),
            'date' => $data['date'],
            'branch_id' => $data['branch_id'] ?? null,
            'status' => 'in_progress',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);

        $products = Product::where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            $onHand = $this->inventoryService->getQuantityOnHand(
                $companyId,
                $product->id,
                $data['branch_id'] ?? null
            );

            $avgCost = $this->getAverageCost($companyId, $product->id, $data['branch_id'] ?? null);

            StockCountLine::create([
                'stock_count_id' => $count->id,
                'product_id' => $product->id,
                'expected_quantity' => $onHand,
                'counted_quantity' => null,
                'variance_quantity' => 0,
                'unit_cost' => $avgCost,
                'variance_cost' => 0,
            ]);
        }

        return $count->fresh('lines.product');
    }

    public function updateCountLines(StockCount $count, array $lines): StockCount
    {
        if ($count->status !== 'in_progress') {
            throw new InvalidArgumentException('Only in-progress counts can be updated.');
        }

        foreach ($lines as $lineId => $countedQty) {
            $line = StockCountLine::where('id', $lineId)
                ->where('stock_count_id', $count->id)
                ->first();

            if ($line) {
                $counted = $countedQty !== null ? (float) $countedQty : null;
                $variance = $counted !== null ? round($counted - (float) $line->expected_quantity, 4) : 0;
                $varianceCost = round(abs($variance) * (float) $line->unit_cost, 2);

                $line->update([
                    'counted_quantity' => $counted,
                    'variance_quantity' => $variance,
                    'variance_cost' => $varianceCost,
                ]);
            }
        }

        return $count->fresh('lines.product');
    }

    public function postCount(StockCount $count, int $userId): StockCount
    {
        if ($count->status !== 'in_progress') {
            throw new InvalidArgumentException('Only in-progress counts can be posted.');
        }

        $countedLines = $count->lines->filter(fn($line) => $line->counted_quantity !== null);

        if ($countedLines->isEmpty()) {
            throw new InvalidArgumentException('No counted lines found. Count at least one product.');
        }

        return DB::transaction(function () use ($count, $countedLines, $userId) {
            $companyId = $count->company_id;

            $invAssetAccount = DefaultAccountMapping::getAccount($companyId, 'inventory_asset');
            $varianceAccount = DefaultAccountMapping::getAccount($companyId, 'inventory_count_variance');

            if (!$invAssetAccount || !$varianceAccount) {
                throw new InvalidArgumentException('Required accounts (1200 or 6850) not found.');
            }

            $lines = [];

            foreach ($countedLines as $line) {
                if ($line->variance_quantity == 0) {
                    continue;
                }

                $isIncrease = $line->variance_quantity > 0;

                if ($isIncrease) {
                    $this->inventoryService->receiveStock(
                        $companyId,
                        $line->product_id,
                        $count->branch_id,
                        abs($line->variance_quantity),
                        (float) $line->unit_cost,
                        'stock_count',
                        $count->id,
                        $count->date->format('Y-m-d')
                    );
                } else {
                    $this->inventoryService->consumeStock(
                        $companyId,
                        $line->product_id,
                        $count->branch_id,
                        abs($line->variance_quantity),
                        $count->date->format('Y-m-d')
                    );
                }

                if ($isIncrease) {
                    $lines[] = [
                        'account_id' => $invAssetAccount->id,
                        'debit' => (float) $line->variance_cost,
                        'credit' => 0,
                        'memo' => "Stock count variance - {$line->product->name}",
                        'branch_id' => $count->branch_id,
                    ];
                    $lines[] = [
                        'account_id' => $varianceAccount->id,
                        'debit' => 0,
                        'credit' => (float) $line->variance_cost,
                        'memo' => "Stock count variance - {$line->product->name}",
                        'branch_id' => $count->branch_id,
                    ];
                } else {
                    $lines[] = [
                        'account_id' => $varianceAccount->id,
                        'debit' => (float) $line->variance_cost,
                        'credit' => 0,
                        'memo' => "Stock count variance - {$line->product->name}",
                        'branch_id' => $count->branch_id,
                    ];
                    $lines[] = [
                        'account_id' => $invAssetAccount->id,
                        'debit' => 0,
                        'credit' => (float) $line->variance_cost,
                        'memo' => "Stock count variance - {$line->product->name}",
                        'branch_id' => $count->branch_id,
                    ];
                }
            }

            $journalEntry = null;
            if (!empty($lines)) {
                $journalEntry = $this->postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $count->date->format('Y-m-d'),
                    'source_module' => 'stock_count',
                    'reference' => $count->count_number,
                    'memo' => "Stock count {$count->count_number} variance adjustment",
                    'branch_id' => $count->branch_id,
                    'lines' => $lines,
                ]);
            }

            $count->update([
                'status' => 'posted',
                'journal_entry_id' => $journalEntry?->id,
            ]);

            return $count->fresh('lines.product', 'journalEntry');
        });
    }

    protected function getAverageCost(int $companyId, int $productId, ?int $branchId): float
    {
        $query = InventoryCostLayer::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->available();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalQty = (float) $query->sum('quantity_remaining');
        if ($totalQty <= 0) {
            return 0;
        }

        $totalValue = (float) $query->sum(DB::raw('quantity_remaining * unit_cost'));
        return round($totalValue / $totalQty, 4);
    }

    protected function generateCountNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'CNT-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = DB::table('stock_counts')
            ->where('company_id', $companyId)
            ->where('count_number', 'like', $prefix . '%')
            ->orderByDesc('count_number')
            ->first();

        if ($last) {
            $lastSequence = (int) substr($last->count_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }
}
