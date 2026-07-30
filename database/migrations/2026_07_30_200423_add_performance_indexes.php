<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transaction tables: date + status for filtering/sorting
        DB::statement('ALTER TABLE purchase_orders ADD INDEX idx_po_company_status_date (company_id, status, order_date)');
        DB::statement('ALTER TABLE purchase_requisitions ADD INDEX idx_pr_company_status_date (company_id, status, date)');
        DB::statement('ALTER TABLE goods_received_notes ADD INDEX idx_grn_company_status_date (company_id, status, received_date)');
        DB::statement('ALTER TABLE landed_cost_vouchers ADD INDEX idx_lcv_company_status_date (company_id, status, date)');

        // Inventory tables
        DB::statement('ALTER TABLE inventory_adjustments ADD INDEX idx_inv_adj_company_status_date (company_id, status, adjustment_date)');
        DB::statement('ALTER TABLE inventory_transfers ADD INDEX idx_inv_xfer_company_status_date (company_id, status, transfer_date)');
        DB::statement('ALTER TABLE inventory_cost_layers ADD INDEX idx_inv_cl_product_available (product_id, quantity_remaining)');
        DB::statement('ALTER TABLE stock_counts ADD INDEX idx_stock_count_company_date (company_id, count_date)');

        // Financial tables
        DB::statement('ALTER TABLE budgets ADD INDEX idx_budgets_company_status (company_id, status)');
        DB::statement('ALTER TABLE cheques ADD INDEX idx_cheques_company_date (company_id, cheque_date)');

        // Foreign keys commonly used in JOINs
        DB::statement('ALTER TABLE journal_entry_lines ADD INDEX idx_jel_account_id (account_id)');
        DB::statement('ALTER TABLE journal_entry_lines ADD INDEX idx_jel_journal_entry_id (journal_entry_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE purchase_orders DROP INDEX idx_po_company_status_date');
        DB::statement('ALTER TABLE purchase_requisitions DROP INDEX idx_pr_company_status_date');
        DB::statement('ALTER TABLE goods_received_notes DROP INDEX idx_grn_company_status_date');
        DB::statement('ALTER TABLE landed_cost_vouchers DROP INDEX idx_lcv_company_status_date');
        DB::statement('ALTER TABLE inventory_adjustments DROP INDEX idx_inv_adj_company_status_date');
        DB::statement('ALTER TABLE inventory_transfers DROP INDEX idx_inv_xfer_company_status_date');
        DB::statement('ALTER TABLE inventory_cost_layers DROP INDEX idx_inv_cl_product_available');
        DB::statement('ALTER TABLE stock_counts DROP INDEX idx_stock_count_company_date');
        DB::statement('ALTER TABLE budgets DROP INDEX idx_budgets_company_status');
        DB::statement('ALTER TABLE cheques DROP INDEX idx_cheques_company_date');
        DB::statement('ALTER TABLE journal_entry_lines DROP INDEX idx_jel_account_id');
        DB::statement('ALTER TABLE journal_entry_lines DROP INDEX idx_jel_journal_entry_id');
    }
};
