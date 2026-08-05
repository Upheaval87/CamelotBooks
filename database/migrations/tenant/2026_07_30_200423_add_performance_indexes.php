<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        // Transaction tables: date + status for filtering/sorting
        ['purchase_orders', 'idx_po_company_status_date', ['company_id', 'status', 'date']],
        ['purchase_requisitions', 'idx_pr_company_status_date', ['company_id', 'status', 'date']],
        ['goods_received_notes', 'idx_grn_company_status_date', ['company_id', 'status', 'date']],
        ['landed_cost_vouchers', 'idx_lcv_company_status_date', ['company_id', 'status', 'date']],

        // Inventory tables
        ['inventory_adjustments', 'idx_inv_adj_company_status_date', ['company_id', 'status', 'date']],
        ['inventory_transfers', 'idx_inv_xfer_company_status_date', ['company_id', 'status', 'date']],
        ['inventory_cost_layers', 'idx_inv_cl_product_available', ['product_id', 'quantity_remaining']],
        ['stock_counts', 'idx_stock_count_company_date', ['company_id', 'date']],

        // Financial tables
        ['budgets', 'idx_budgets_company_status', ['company_id', 'status']],
        ['cheques', 'idx_cheques_company_date', ['company_id', 'date']],

        // Foreign keys commonly used in JOINs
        ['journal_entry_lines', 'idx_jel_account_id', ['account_id']],
        ['journal_entry_lines', 'idx_jel_journal_entry_id', ['journal_entry_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $index, $columns]) {
            if (!Schema::hasTable($table) || !Schema::hasColumns($table, $columns)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($index, $columns) {
                $t->index($columns, $index);
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, $index]) {
            if (!Schema::hasTable($table) || !Schema::hasIndex($table, $index)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($index) {
                $t->dropIndex($index);
            });
        }
    }
};
