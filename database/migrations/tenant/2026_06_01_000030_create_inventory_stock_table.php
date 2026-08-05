<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'product_id', 'branch_id'], 'inv_stock_company_product_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock');
    }
};
