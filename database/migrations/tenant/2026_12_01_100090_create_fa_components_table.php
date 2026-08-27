<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->integer('useful_life')->comment('Months');
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['asset_id', 'status']);
            $table->index(['company_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_components');
    }
};
