<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_dep_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets')->cascadeOnDelete();
            $table->string('book_type', 10)->comment('financial or tax');
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->integer('useful_life')->comment('Months');
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('depreciation_rate', 7, 4)->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);
            $table->date('last_run_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['asset_id', 'book_type']);
            $table->index(['company_id', 'book_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_dep_books');
    }
};
