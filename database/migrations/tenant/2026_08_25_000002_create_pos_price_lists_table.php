<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('type', 30)->default('retail');
            $table->string('applies_to', 30)->default('all');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('pos_price_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_list_id');
            $table->foreign('price_list_id')->references('id')->on('pos_price_lists')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->decimal('unit_price', 15, 2);
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->nullable();
            $table->timestamps();

            $table->unique(['price_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_price_list_items');
        Schema::dropIfExists('pos_price_lists');
    }
};
