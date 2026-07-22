<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('type', 20)->default('service');
            $table->decimal('sales_price', 15, 2);
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->foreignId('income_account_id')->constrained('accounts');
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
