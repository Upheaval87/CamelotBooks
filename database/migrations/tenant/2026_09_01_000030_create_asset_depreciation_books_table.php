<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_depreciation_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('book_type', 10)->comment('financial or tax');

            $table->string('depreciation_method', 30);
            $table->integer('useful_life')->comment('Months');
            $table->string('residual_value_type', 10)->default('amount');
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('depreciation_rate', 7, 4)->nullable();
            $table->decimal('total_estimated_units', 12, 2)->nullable()->comment('For UOP method');
            $table->integer('sum_of_years_digits')->nullable();

            $table->decimal('current_cost', 15, 2)->comment('Initially acquisition_cost, updated by revaluation');
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('accumulated_impairment', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);

            $table->date('last_depreciation_date')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->unique(['asset_id', 'book_type']);
            $table->index(['status', 'last_depreciation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_books');
    }
};
