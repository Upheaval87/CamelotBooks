<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices: add a `settled` accumulator (additive only). Settlement of a
        // receipt-from-invoice flow EXTENDS the existing amount_paid/status model.
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'settled')) {
                $table->decimal('settled', 15, 2)->default(0)->after('amount_paid');
            }
        });

        // sales_receipts: link a receipt to the invoice it settles (nullable so
        // standalone/walk-in receipts remain supported).
        Schema::table('sales_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_receipts', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('customer_id')
                    ->constrained('invoices')->nullOnDelete();
                $table->index('invoice_id');
            }
        });

        // invoice_allocations: one row per (receipt, payment, invoice) applied amount.
        Schema::create('invoice_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receipt_id')->constrained('sales_receipts')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('sales_receipt_payments')->nullOnDelete();
            $table->decimal('applied_amount', 15, 2);
            $table->timestamp('created_at')->nullable();

            $table->index('invoice_id');
            $table->index('receipt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_allocations');

        Schema::table('sales_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('sales_receipts', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'settled')) {
                $table->dropColumn('settled');
            }
        });
    }
};
