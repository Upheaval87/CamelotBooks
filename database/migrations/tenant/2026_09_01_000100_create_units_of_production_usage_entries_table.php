<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_production_usage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->decimal('units_used', 12, 2);
            $table->decimal('cumulative_units', 12, 2)->default(0);
            $table->text('memo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['asset_id', 'period_start_date', 'period_end_date'], 'uop_usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_production_usage_entries');
    }
};
