<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->nullOnDelete(); // nullable for walk-ins
            $table->string('receipt_number', 30);
            $table->date('receipt_date');
            $table->string('reference', 100)->nullable();
            $table->text('memo')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('eis_submission_id')->nullable()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'receipt_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'receipt_date']);
        });

        Schema::create('sales_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->foreignId('income_account_id')->constrained('accounts');
            $table->decimal('cost_of_goods', 15, 2)->default(0);
            $table->foreignId('cost_center_id')->nullable()->nullOnDelete();
            $table->timestamps();

            $table->index('sales_receipt_id');
        });

        Schema::create('sales_receipt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('pos_payment_methods');
            $table->decimal('amount', 15, 2);
            $table->decimal('cash_tendered', 15, 2)->nullable();
            $table->decimal('change_given', 15, 2)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('account_name', 100)->nullable();
            $table->string('institution', 100)->nullable();
            $table->foreignId('bank_account_id')->nullable()->nullOnDelete(); // For Bank Transfer: which bank account received the funds
            $table->timestamps();

            $table->index('sales_receipt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_receipt_payments');
        Schema::dropIfExists('sales_receipt_lines');
        Schema::dropIfExists('sales_receipts');
    }
};
