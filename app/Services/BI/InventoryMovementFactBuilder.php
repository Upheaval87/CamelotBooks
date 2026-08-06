<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\MartConnection;
use Illuminate\Support\Facades\DB;

class InventoryMovementFactBuilder
{
    use MartConnection;

    public function build(?int $companyId = null): int
    {
        $now = now();

        $this->buildReceipts($now, $companyId);
        $this->buildConsumptions($now, $companyId);
        $this->buildAdjustments($now, $companyId);
        $this->buildTransfers($now, $companyId);

        return $this->martTable('fact_inventory_movement')->count();
    }

    protected function buildReceipts(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        $this->martTable('grn_lines AS gl')
            ->join('goods_received_notes AS grn', 'grn.id', '=', 'gl.goods_received_note_id')
            ->where('grn.status', 'posted')
            ->when($companyId, fn ($q) => $q->where('grn.company_id', $companyId))
            ->select(
                'grn.company_id',
                'grn.date',
                'grn.branch_id',
                'gl.product_id',
                DB::raw("'receipt' AS movement_type"),
                'gl.quantity_received AS quantity',
                'gl.unit_cost',
                'gl.total_cost',
                DB::raw("'goods_received_note' AS source_type"),
                'grn.id AS source_id',
                'grn.grn_number AS reference_number'
            )
            ->orderBy('gl.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'      => $row->company_id,
                        'date_key'         => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'       => $row->branch_id,
                        'item_key'         => $row->product_id,
                        'movement_type'    => $row->movement_type,
                        'quantity'         => $row->quantity,
                        'unit_cost'        => $row->unit_cost,
                        'total_cost'       => $row->total_cost,
                        'source_type'      => $row->source_type,
                        'source_id'        => $row->source_id,
                        'reference_number' => $row->reference_number,
                        'refreshed_at'     => $now,
                    ];
                }

                $this->martTable('fact_inventory_movement')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_inventory_movement')->insert($inserts);
        }
    }

    protected function buildConsumptions(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        $this->martTable('invoice_lines AS il')
            ->join('invoices AS i', 'i.id', '=', 'il.invoice_id')
            ->join('products AS p', 'p.id', '=', 'il.product_id')
            ->where('i.status', '!=', 'void')
            ->where('p.tracked_as_inventory', true)
            ->when($companyId, fn ($q) => $q->where('i.company_id', $companyId))
            ->select(
                'i.company_id',
                'i.invoice_date AS date',
                'i.branch_id',
                'il.product_id',
                DB::raw("'consumption' AS movement_type"),
                DB::raw("-il.quantity AS quantity"),
                DB::raw("0 AS unit_cost"),
                DB::raw("0 AS total_cost"),
                DB::raw("'invoice' AS source_type"),
                'i.id AS source_id',
                'i.invoice_number AS reference_number'
            )
            ->orderBy('il.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'      => $row->company_id,
                        'date_key'         => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'       => $row->branch_id,
                        'item_key'         => $row->product_id,
                        'movement_type'    => $row->movement_type,
                        'quantity'         => $row->quantity,
                        'unit_cost'        => $row->unit_cost,
                        'total_cost'       => $row->total_cost,
                        'source_type'      => $row->source_type,
                        'source_id'        => $row->source_id,
                        'reference_number' => $row->reference_number,
                        'refreshed_at'     => $now,
                    ];
                }

                $this->martTable('fact_inventory_movement')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_inventory_movement')->insert($inserts);
        }
    }

    protected function buildAdjustments(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        $this->martTable('inventory_adjustments AS ia')
            ->where('ia.status', 'posted')
            ->when($companyId, fn ($q) => $q->where('ia.company_id', $companyId))
            ->select(
                'ia.company_id',
                'ia.date',
                'ia.branch_id',
                'ia.product_id',
                DB::raw("CASE WHEN ia.type = 'increase' THEN 'adjustment_increase' ELSE 'adjustment_decrease' END AS movement_type"),
                DB::raw("CASE WHEN ia.type = 'increase' THEN ia.quantity ELSE -ia.quantity END AS quantity"),
                'ia.unit_cost',
                'ia.total_cost',
                DB::raw("'inventory_adjustment' AS source_type"),
                'ia.id AS source_id',
                'ia.adjustment_number AS reference_number'
            )
            ->orderBy('ia.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'      => $row->company_id,
                        'date_key'         => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'       => $row->branch_id,
                        'item_key'         => $row->product_id,
                        'movement_type'    => $row->movement_type,
                        'quantity'         => $row->quantity,
                        'unit_cost'        => $row->unit_cost ?? 0,
                        'total_cost'       => $row->total_cost ?? 0,
                        'source_type'      => $row->source_type,
                        'source_id'        => $row->source_id,
                        'reference_number' => $row->reference_number,
                        'refreshed_at'     => $now,
                    ];
                }

                $this->martTable('fact_inventory_movement')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_inventory_movement')->insert($inserts);
        }
    }

    protected function buildTransfers(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        // Transfer OUT (from source branch)
        $this->martTable('inventory_transfers AS it')
            ->where('it.status', 'completed')
            ->when($companyId, fn ($q) => $q->where('it.company_id', $companyId))
            ->select(
                'it.company_id',
                'it.date',
                'it.from_branch_id AS branch_key',
                'it.product_id',
                DB::raw("'transfer_out' AS movement_type"),
                DB::raw("-it.quantity AS quantity"),
                DB::raw("0 AS unit_cost"),
                DB::raw("0 AS total_cost"),
                DB::raw("'inventory_transfer' AS source_type"),
                'it.id AS source_id',
                'it.transfer_number AS reference_number'
            )
            ->orderBy('it.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'      => $row->company_id,
                        'date_key'         => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'       => $row->branch_key,
                        'item_key'         => $row->product_id,
                        'movement_type'    => $row->movement_type,
                        'quantity'         => $row->quantity,
                        'unit_cost'        => $row->unit_cost,
                        'total_cost'       => $row->total_cost,
                        'source_type'      => $row->source_type,
                        'source_id'        => $row->source_id,
                        'reference_number' => $row->reference_number,
                        'refreshed_at'     => $now,
                    ];
                }

                $this->martTable('fact_inventory_movement')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_inventory_movement')->insert($inserts);
            $inserts = [];
        }

        // Transfer IN (to destination branch)
        $this->martTable('inventory_transfers AS it')
            ->where('it.status', 'completed')
            ->when($companyId, fn ($q) => $q->where('it.company_id', $companyId))
            ->select(
                'it.company_id',
                'it.date',
                'it.to_branch_id AS branch_key',
                'it.product_id',
                DB::raw("'transfer_in' AS movement_type"),
                'it.quantity',
                DB::raw("0 AS unit_cost"),
                DB::raw("0 AS total_cost"),
                DB::raw("'inventory_transfer' AS source_type"),
                'it.id AS source_id',
                'it.transfer_number AS reference_number'
            )
            ->orderBy('it.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'      => $row->company_id,
                        'date_key'         => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'       => $row->branch_key,
                        'item_key'         => $row->product_id,
                        'movement_type'    => $row->movement_type,
                        'quantity'         => $row->quantity,
                        'unit_cost'        => $row->unit_cost,
                        'total_cost'       => $row->total_cost,
                        'source_type'      => $row->source_type,
                        'source_id'        => $row->source_id,
                        'reference_number' => $row->reference_number,
                        'refreshed_at'     => $now,
                    ];
                }

                $this->martTable('fact_inventory_movement')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_inventory_movement')->insert($inserts);
        }
    }
}
