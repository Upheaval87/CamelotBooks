<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'customers',
            'journal_entries',
            'vendors',
            'recurring_journal_templates',
            'recurring_journal_template_lines',
            'invoices',
            'recurring_invoice_templates',
            'bills',
            'recurring_bill_templates',
            'credit_notes',
            'vendor_payments',
            'customer_payments',
            'vendor_credits',
            'bank_transactions',
            'inventory_cost_layers',
            'inventory_adjustments',
            'inventory_stock',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'cost_center_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'customers',
            'journal_entries',
            'vendors',
            'recurring_journal_templates',
            'recurring_journal_template_lines',
            'invoices',
            'recurring_invoice_templates',
            'bills',
            'recurring_bill_templates',
            'credit_notes',
            'vendor_payments',
            'customer_payments',
            'vendor_credits',
            'bank_transactions',
            'inventory_cost_layers',
            'inventory_adjustments',
            'inventory_stock',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'cost_center_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['cost_center_id']);
                    $table->dropColumn('cost_center_id');
                });
            }
        }
    }
};
