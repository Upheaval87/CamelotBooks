<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Dimensions ──────────────────────────────────────────────

        Schema::create('dim_date', function (Blueprint $table) {
            $table->unsignedInteger('date_key')->primary();
            $table->date('full_date');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week');
            $table->unsignedTinyInteger('day_of_month');
            $table->unsignedTinyInteger('day_of_week');
            $table->string('month_name', 20);
            $table->string('quarter_name', 10);
            $table->boolean('is_weekend');
            $table->unsignedSmallInteger('fiscal_year_label')->nullable();
            $table->unsignedTinyInteger('fiscal_quarter')->nullable();
            $table->unsignedTinyInteger('fiscal_month')->nullable();
            $table->unsignedBigInteger('fiscal_year_id')->nullable();

            $table->index('full_date');
            $table->index('year');
            $table->index(['fiscal_year_label', 'fiscal_quarter']);
        });

        Schema::create('dim_company', function (Blueprint $table) {
            $table->unsignedBigInteger('company_key')->primary();
            $table->string('company_code', 50);
            $table->string('company_name', 255);
            $table->char('base_currency', 3);
            $table->unsignedTinyInteger('fiscal_year_start_month');
            $table->boolean('is_active');
            $table->timestamp('synced_at');
        });

        Schema::create('dim_branch', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->string('branch_code', 50);
            $table->string('branch_name', 255);
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
        });

        Schema::create('dim_cost_center', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->string('cost_center_code', 50);
            $table->string('cost_center_name', 255);
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
        });

        Schema::create('dim_account', function (Blueprint $table) {
            $table->unsignedBigInteger('account_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->string('account_code', 50);
            $table->string('account_name', 255);
            $table->string('account_type', 20);
            $table->string('account_sub_type', 50)->nullable();
            $table->boolean('is_bank_account');
            $table->boolean('is_non_cash');
            $table->string('cash_flow_section', 30)->nullable();
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
            $table->index('account_type');
        });

        Schema::create('dim_item', function (Blueprint $table) {
            $table->unsignedBigInteger('item_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->string('sku', 50);
            $table->string('item_name', 255);
            $table->string('item_type', 20);
            $table->boolean('tracked_as_inventory');
            $table->decimal('sales_price', 15, 2)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
        });

        Schema::create('dim_customer', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->string('customer_name', 255);
            $table->string('email', 255)->nullable();
            $table->char('currency', 3);
            $table->string('payment_terms', 50)->nullable();
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
        });

        Schema::create('dim_vendor', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->string('vendor_name', 255);
            $table->string('email', 255)->nullable();
            $table->char('currency', 3);
            $table->string('payment_terms', 50)->nullable();
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
        });

        Schema::create('dim_employee', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_key')->primary();
            $table->unsignedBigInteger('company_key');
            $table->unsignedBigInteger('branch_key')->nullable();
            $table->unsignedBigInteger('cost_center_key')->nullable();
            $table->string('employee_number', 50);
            $table->string('full_name', 255);
            $table->string('position', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->string('employment_status', 20);
            $table->boolean('is_active');
            $table->timestamp('synced_at');

            $table->index('company_key');
        });

        // ── Fact Tables ─────────────────────────────────────────────

        Schema::create('fact_general_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_key');
            $table->unsignedInteger('date_key');
            $table->unsignedBigInteger('account_key');
            $table->unsignedBigInteger('branch_key')->nullable();
            $table->unsignedBigInteger('cost_center_key')->nullable();
            $table->unsignedBigInteger('journal_entry_id');
            $table->string('journal_number', 50);
            $table->string('source_module', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('foreign_amount', 18, 2)->nullable();
            $table->char('foreign_currency', 3)->nullable();
            $table->decimal('exchange_rate', 18, 8)->nullable();
            $table->text('memo')->nullable();
            $table->timestamp('refreshed_at');

            $table->index('company_key');
            $table->index('date_key');
            $table->index('account_key');
            $table->index('branch_key');
            $table->index('source_module');
            $table->index(['company_key', 'date_key']);
            $table->index(['company_key', 'account_key']);
            $table->index(['company_key', 'branch_key', 'date_key']);
        });

        Schema::create('fact_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_key');
            $table->unsignedInteger('date_key');
            $table->unsignedBigInteger('branch_key')->nullable();
            $table->unsignedBigInteger('cost_center_key')->nullable();
            $table->unsignedBigInteger('customer_key')->nullable();
            $table->unsignedBigInteger('item_key')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->string('invoice_number', 50);
            $table->string('invoice_status', 20);
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedBigInteger('income_account_key')->nullable();
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->boolean('is_credit_note')->default(false);
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->timestamp('refreshed_at');

            $table->index('company_key');
            $table->index('date_key');
            $table->index('customer_key');
            $table->index('item_key');
            $table->index(['company_key', 'date_key']);
            $table->index(['company_key', 'customer_key']);
        });

        Schema::create('fact_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_key');
            $table->unsignedInteger('date_key');
            $table->unsignedBigInteger('branch_key')->nullable();
            $table->unsignedBigInteger('cost_center_key')->nullable();
            $table->unsignedBigInteger('vendor_key')->nullable();
            $table->unsignedBigInteger('item_key')->nullable();
            $table->string('source_type', 20);
            $table->unsignedBigInteger('source_id');
            $table->string('source_number', 50);
            $table->string('source_status', 20);
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedBigInteger('expense_account_key');
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->timestamp('refreshed_at');

            $table->index('company_key');
            $table->index('date_key');
            $table->index('vendor_key');
            $table->index('item_key');
            $table->index(['company_key', 'date_key']);
            $table->index(['company_key', 'vendor_key']);
        });

        Schema::create('fact_payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_key');
            $table->unsignedInteger('date_key');
            $table->unsignedBigInteger('branch_key')->nullable();
            $table->unsignedBigInteger('cost_center_key')->nullable();
            $table->unsignedBigInteger('employee_key');
            $table->unsignedBigInteger('payroll_run_id');
            $table->string('run_number', 50);
            $table->string('period_label', 50);
            $table->decimal('basic_pay', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('gross_pay', 15, 2)->default(0);
            $table->decimal('paye', 15, 2)->default(0);
            $table->decimal('pension_ee', 15, 2)->default(0);
            $table->decimal('pension_er', 15, 2)->default(0);
            $table->decimal('employer_pension_expense', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->timestamp('refreshed_at');

            $table->index('company_key');
            $table->index('date_key');
            $table->index('employee_key');
            $table->index(['company_key', 'date_key']);
            $table->index(['company_key', 'branch_key']);
        });

        Schema::create('fact_inventory_movement', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_key');
            $table->unsignedInteger('date_key');
            $table->unsignedBigInteger('branch_key')->nullable();
            $table->unsignedBigInteger('item_key');
            $table->string('movement_type', 30);
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->timestamp('refreshed_at');

            $table->index('company_key');
            $table->index('date_key');
            $table->index('item_key');
            $table->index('branch_key');
            $table->index(['company_key', 'date_key']);
            $table->index(['company_key', 'item_key']);
            $table->index(['company_key', 'movement_type']);
        });

        // ── Refresh Log ─────────────────────────────────────────────

        Schema::create('bi_refresh_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 20)->default('running');
            $table->json('rows_refreshed')->nullable();
            $table->text('error_message')->nullable();
            $table->string('triggered_by', 20)->default('artisan');
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_refresh_log');
        Schema::dropIfExists('fact_inventory_movement');
        Schema::dropIfExists('fact_payroll');
        Schema::dropIfExists('fact_purchases');
        Schema::dropIfExists('fact_sales');
        Schema::dropIfExists('fact_general_ledger');
        Schema::dropIfExists('dim_employee');
        Schema::dropIfExists('dim_vendor');
        Schema::dropIfExists('dim_customer');
        Schema::dropIfExists('dim_item');
        Schema::dropIfExists('dim_account');
        Schema::dropIfExists('dim_cost_center');
        Schema::dropIfExists('dim_branch');
        Schema::dropIfExists('dim_company');
        Schema::dropIfExists('dim_date');
    }
};
