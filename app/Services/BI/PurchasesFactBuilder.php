<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;

class PurchasesFactBuilder
{
    public function build(): int
    {
        $now = now();

        $this->buildBillLines($now);
        $this->buildExpenseLines($now);

        return DB::table('fact_purchases')->count();
    }

    protected function buildBillLines(string $now): void
    {
        $inserts = [];

        DB::table('bill_lines AS bl')
            ->join('bills AS b', 'b.id', '=', 'bl.bill_id')
            ->where('b.status', '!=', 'void')
            ->select(
                'b.company_id',
                'b.bill_date AS date',
                'b.branch_id',
                'bl.cost_center_id',
                'b.vendor_id',
                'bl.product_id',
                DB::raw("'bill' AS source_type"),
                'b.id AS source_id',
                DB::raw("COALESCE(b.bill_number, b.internal_number) AS source_number"),
                'b.status AS source_status',
                'bl.quantity',
                'bl.unit_price',
                'bl.discount',
                'bl.tax_rate',
                'bl.amount',
                'bl.tax_amount',
                'bl.line_total',
                'bl.expense_account_id'
            )
            ->orderBy('bl.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'        => $row->company_id,
                        'date_key'           => (int) $row->date,
                        'branch_key'         => $row->branch_id,
                        'cost_center_key'    => $row->cost_center_id,
                        'vendor_key'         => $row->vendor_id,
                        'item_key'           => $row->product_id,
                        'source_type'        => $row->source_type,
                        'source_id'          => $row->source_id,
                        'source_number'      => $row->source_number,
                        'source_status'      => $row->source_status,
                        'quantity'           => $row->quantity,
                        'unit_price'         => $row->unit_price,
                        'discount'           => $row->discount,
                        'tax_rate'           => $row->tax_rate,
                        'amount'             => $row->amount,
                        'tax_amount'         => $row->tax_amount,
                        'line_total'         => $row->line_total,
                        'expense_account_key' => $row->expense_account_id,
                        'base_amount'        => null,
                        'refreshed_at'       => $now,
                    ];
                }

                DB::table('fact_purchases')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            DB::table('fact_purchases')->insert($inserts);
        }
    }

    protected function buildExpenseLines(string $now): void
    {
        $inserts = [];

        DB::table('expense_lines AS el')
            ->join('expenses AS e', 'e.id', '=', 'el.expense_id')
            ->where('e.status', '!=', 'void')
            ->select(
                'e.company_id',
                'e.expense_date AS date',
                'e.branch_id',
                'el.cost_center_id',
                'e.vendor_id',
                'el.product_id',
                DB::raw("'expense' AS source_type"),
                'e.id AS source_id',
                DB::raw("COALESCE(e.expense_number, e.reference) AS source_number"),
                'e.status AS source_status',
                'el.quantity',
                'el.unit_price',
                'el.discount',
                'el.tax_rate',
                'el.amount',
                'el.tax_amount',
                'el.line_total',
                'el.expense_account_id'
            )
            ->orderBy('el.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'        => $row->company_id,
                        'date_key'           => (int) $row->date,
                        'branch_key'         => $row->branch_id,
                        'cost_center_key'    => $row->cost_center_id,
                        'vendor_key'         => $row->vendor_id,
                        'item_key'           => $row->product_id,
                        'source_type'        => $row->source_type,
                        'source_id'          => $row->source_id,
                        'source_number'      => $row->source_number,
                        'source_status'      => $row->source_status,
                        'quantity'           => $row->quantity,
                        'unit_price'         => $row->unit_price,
                        'discount'           => $row->discount,
                        'tax_rate'           => $row->tax_rate,
                        'amount'             => $row->amount,
                        'tax_amount'         => $row->tax_amount,
                        'line_total'         => $row->line_total,
                        'expense_account_key' => $row->expense_account_id,
                        'base_amount'        => null,
                        'refreshed_at'       => $now,
                    ];
                }

                DB::table('fact_purchases')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            DB::table('fact_purchases')->insert($inserts);
        }
    }
}
