<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoice_template_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rit_id');
            $table->foreign('rit_id')->references('id')->on('recurring_invoice_templates')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->foreignId('income_account_id')->constrained('accounts');
            $table->timestamps();

            $table->index('rit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_template_lines');
    }
};
