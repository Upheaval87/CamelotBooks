<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('pos_terminals');
            $table->foreignId('cashier_session_id')->nullable()->constrained('pos_cashier_sessions');
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained();
            $table->string('sale_number', 50);
            $table->string('reference')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->boolean('is_on_account')->default(false);
            $table->boolean('synced_from_offline')->default(false);
            $table->string('offline_transaction_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->timestamps();

            $table->unique(['company_id', 'sale_number']);
            $table->index(['company_id', 'terminal_id', 'status']);
            $table->index(['company_id', 'cashier_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};
