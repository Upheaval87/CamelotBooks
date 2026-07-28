<?php

namespace App\Services\Reporting;

use App\Models\InventoryAdjustment;
use App\Models\InventoryCostLayer;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function generate(int $companyId, ?int $productId = null, ?int $branchId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $movements = collect();

        // Receives from cost layers
        $receives = InventoryCostLayer::where('company_id', $companyId)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->get()
            ->map(fn ($layer) => [
                'date' => $layer->date,
                'product_id' => $layer->product_id,
                'branch_id' => $layer->branch_id,
                'type' => 'receive',
                'reference' => $layer->source_type . '#' . $layer->source_id,
                'quantity' => (float) $layer->quantity_remaining + 0, // received qty
                'unit_cost' => (float) $layer->unit_cost,
                'running_qty' => 0,
            ]);

        // Transfers out
        $transfersOut = InventoryTransfer::where('company_id', $companyId)
            ->where('status', 'completed')
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($branchId, fn ($q) => $q->where('from_branch_id', $branchId))
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->get()
            ->map(fn ($t) => [
                'date' => $t->date,
                'product_id' => $t->product_id,
                'branch_id' => $t->from_branch_id,
                'type' => 'transfer_out',
                'reference' => 'TRF-' . $t->transfer_number,
                'quantity' => -(float) $t->quantity,
                'unit_cost' => 0,
                'running_qty' => 0,
            ]);

        // Transfers in
        $transfersIn = InventoryTransfer::where('company_id', $companyId)
            ->where('status', 'completed')
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($branchId, fn ($q) => $q->where('to_branch_id', $branchId))
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->get()
            ->map(fn ($t) => [
                'date' => $t->date,
                'product_id' => $t->product_id,
                'branch_id' => $t->to_branch_id,
                'type' => 'transfer_in',
                'reference' => 'TRF-' . $t->transfer_number,
                'quantity' => (float) $t->quantity,
                'unit_cost' => 0,
                'running_qty' => 0,
            ]);

        // Adjustments
        $adjustments = InventoryAdjustment::where('company_id', $companyId)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->get()
            ->map(function ($a) {
                $qty = (float) $a->quantity;
                if ($a->type === 'decrease') {
                    $qty = -$qty;
                }
                return [
                    'date' => $a->date,
                    'product_id' => $a->product_id,
                    'branch_id' => $a->branch_id,
                    'type' => 'adjustment',
                    'reference' => $a->adjustment_number,
                    'quantity' => $qty,
                    'unit_cost' => (float) $a->unit_cost,
                    'running_qty' => 0,
                ];
            });

        $movements = $receives->merge($transfersOut)->merge($transfersIn)->merge($adjustments)
            ->sortBy('date')
            ->values();

        // Compute running quantities per product+branch
        $runningTotals = [];
        foreach ($movements as &$m) {
            $key = $m['product_id'] . '_' . ($m['branch_id'] ?? 'null');
            $runningTotals[$key] = ($runningTotals[$key] ?? 0) + $m['quantity'];
            $m['running_qty'] = $runningTotals[$key];
        }
        unset($m);

        // Enrich with product names
        $productIds = $movements->pluck('product_id')->unique()->filter();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $enriched = $movements->map(function ($m) use ($products) {
            $m['product_name'] = $products[$m['product_id']]->name ?? 'N/A';
            $m['sku'] = $products[$m['product_id']]->sku ?? '';
            return $m;
        });

        // Reconciliation: compare running qty to inventory_stock
        $reconciliation = [];
        foreach ($runningTotals as $key => $runningQty) {
            [$pid, $bid] = explode('_', $key, 2);
            $stock = InventoryStock::where('company_id', $companyId)
                ->where('product_id', (int) $pid)
                ->where('branch_id', $bid === 'null' ? null : (int) $bid)
                ->first();
            $onHand = $stock ? (float) $stock->quantity_on_hand : 0;
            $reconciliation[] = [
                'product_id' => (int) $pid,
                'branch_id' => $bid === 'null' ? null : (int) $bid,
                'product_name' => $products[(int) $pid]->name ?? 'N/A',
                'running_qty' => $runningQty,
                'on_hand' => $onHand,
                'variance' => round($runningQty - $onHand, 4),
            ];
        }

        return [
            'movements' => $enriched,
            'reconciliation' => $reconciliation,
            'product_filter' => $productId ? $products[$productId] ?? null : null,
        ];
    }
}
