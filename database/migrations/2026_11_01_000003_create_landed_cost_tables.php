<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landed_cost_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_number', 50);
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->enum('allocation_method', ['by_value', 'by_quantity'])->default('by_value');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'voucher_number']);
        });

        Schema::create('landed_cost_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('landed_cost_vouchers')->cascadeOnDelete();
            $table->enum('component_type', ['freight', 'customs', 'insurance', 'handling', 'other']);
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->foreignId('payee_account_id')->constrained('accounts');
            $table->timestamps();
        });

        Schema::create('landed_cost_voucher_grns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('landed_cost_vouchers')->cascadeOnDelete();
            $table->foreignId('grn_id')->constrained('goods_received_notes');
            $table->timestamps();

            $table->unique(['voucher_id', 'grn_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_cost_voucher_grns');
        Schema::dropIfExists('landed_cost_components');
        Schema::dropIfExists('landed_cost_vouchers');
    }
};
