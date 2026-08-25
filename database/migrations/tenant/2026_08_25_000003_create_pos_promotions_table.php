<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('type', 30)->default('percentage');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->nullable();
            $table->string('customer_group', 60)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->string('applies_to', 30)->default('all_items');
            $table->json('applies_to_ids')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_promotions');
    }
};
