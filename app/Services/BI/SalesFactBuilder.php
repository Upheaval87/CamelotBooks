<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\MartConnection;
use Illuminate\Support\Facades\DB;

class SalesFactBuilder
{
    use MartConnection;

    public function build(?int $companyId = null): int
    {
        $now = now();

        $this->buildInvoiceLines($now, $companyId);
        $this->buildPosSaleLines($now, $companyId);
        $this->buildSalesReceiptLines($now, $companyId);

        return $this->martTable('fact_sales')->count();
    }

    protected function buildInvoiceLines(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        $this->martTable('invoice_lines AS il')
            ->join('invoices AS i', 'i.id', '=', 'il.invoice_id')
            ->where('i.status', '!=', 'void')
            ->when($companyId, fn ($q) => $q->where('i.company_id', $companyId))
            ->select(
                'i.company_id',
                'i.invoice_date AS date',
                'i.branch_id',
                'il.cost_center_id',
                'i.customer_id',
                'il.product_id',
                DB::raw("'invoice' AS source_type"),
                'i.id AS source_id',
                'i.invoice_number AS source_number',
                'i.status AS source_status',
                'il.quantity',
                'il.unit_price',
                'il.discount',
                'il.tax_rate',
                'il.amount',
                'il.tax_amount',
                'il.line_total',
                'il.income_account_id'
            )
            ->orderBy('il.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'       => $row->company_id,
                        'date_key'          => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'        => $row->branch_id,
                        'cost_center_key'   => $row->cost_center_id,
                        'customer_key'      => $row->customer_id,
                        'item_key'          => $row->product_id,
                        'source_type'       => $row->source_type,
                        'source_id'         => $row->source_id,
                        'source_number'     => $row->source_number,
                        'source_status'     => $row->source_status,
                        'quantity'          => $row->quantity,
                        'unit_price'        => $row->unit_price,
                        'discount'          => $row->discount,
                        'tax_rate'          => $row->tax_rate,
                        'amount'            => $row->amount,
                        'tax_amount'        => $row->tax_amount,
                        'line_total'        => $row->line_total,
                        'income_account_key' => $row->income_account_id,
                        'base_amount'       => null,
                        'is_credit_note'    => false,
                        'credit_note_id'    => null,
                        'refreshed_at'      => $now,
                    ];
                }

                $this->martTable('fact_sales')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_sales')->insert($inserts);
        }
    }

    protected function buildPosSaleLines(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        $this->martTable('pos_sale_lines AS psl')
            ->join('pos_sales AS ps', 'ps.id', '=', 'psl.pos_sale_id')
            ->where('ps.status', '!=', 'voided')
            ->when($companyId, fn ($q) => $q->where('ps.company_id', $companyId))
            ->select(
                'ps.company_id',
                'ps.created_at AS date',
                'ps.branch_id',
                'ps.cost_center_id',
                'ps.customer_id',
                'psl.product_id',
                DB::raw("'pos_sale' AS source_type"),
                'ps.id AS source_id',
                'ps.sale_number AS source_number',
                'ps.status AS source_status',
                'psl.quantity',
                'psl.unit_price',
                'psl.discount_amount AS discount',
                'psl.tax_rate',
                'psl.line_total AS amount',
                'psl.tax_amount',
                'psl.line_total',
                DB::raw('NULL AS income_account_id')
            )
            ->orderBy('psl.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'       => $row->company_id,
                        'date_key'          => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'        => $row->branch_id,
                        'cost_center_key'   => $row->cost_center_id,
                        'customer_key'      => $row->customer_id,
                        'item_key'          => $row->product_id,
                        'source_type'       => $row->source_type,
                        'source_id'         => $row->source_id,
                        'source_number'     => $row->source_number,
                        'source_status'     => $row->source_status,
                        'quantity'          => $row->quantity,
                        'unit_price'        => $row->unit_price,
                        'discount'          => $row->discount,
                        'tax_rate'          => $row->tax_rate,
                        'amount'            => $row->amount,
                        'tax_amount'        => $row->tax_amount,
                        'line_total'        => $row->line_total,
                        'income_account_key' => $row->income_account_id,
                        'base_amount'       => null,
                        'is_credit_note'    => false,
                        'credit_note_id'    => null,
                        'refreshed_at'      => $now,
                    ];
                }

                $this->martTable('fact_sales')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_sales')->insert($inserts);
        }
    }

    protected function buildSalesReceiptLines(string $now, ?int $companyId = null): void
    {
        $inserts = [];

        $this->martTable('sales_receipt_lines AS srl')
            ->join('sales_receipts AS sr', 'sr.id', '=', 'srl.sales_receipt_id')
            ->where('sr.status', '!=', 'voided')
            ->when($companyId, fn ($q) => $q->where('sr.company_id', $companyId))
            ->select(
                'sr.company_id',
                'sr.receipt_date AS date',
                'sr.branch_id',
                'srl.cost_center_id',
                'sr.customer_id',
                'srl.product_id',
                DB::raw("'sales_receipt' AS source_type"),
                'sr.id AS source_id',
                'sr.receipt_number AS source_number',
                'sr.status AS source_status',
                'srl.quantity',
                'srl.unit_price',
                'srl.discount',
                'srl.tax_rate',
                'srl.amount',
                'srl.tax_amount',
                'srl.line_total',
                'srl.income_account_id'
            )
            ->orderBy('srl.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'       => $row->company_id,
                        'date_key'          => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'        => $row->branch_id,
                        'cost_center_key'   => $row->cost_center_id,
                        'customer_key'      => $row->customer_id,
                        'item_key'          => $row->product_id,
                        'source_type'       => $row->source_type,
                        'source_id'         => $row->source_id,
                        'source_number'     => $row->source_number,
                        'source_status'     => $row->source_status,
                        'quantity'          => $row->quantity,
                        'unit_price'        => $row->unit_price,
                        'discount'          => $row->discount,
                        'tax_rate'          => $row->tax_rate,
                        'amount'            => $row->amount,
                        'tax_amount'        => $row->tax_amount,
                        'line_total'        => $row->line_total,
                        'income_account_key' => $row->income_account_id,
                        'base_amount'       => null,
                        'is_credit_note'    => false,
                        'credit_note_id'    => null,
                        'refreshed_at'      => $now,
                    ];
                }

                $this->martTable('fact_sales')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_sales')->insert($inserts);
        }
    }
}
