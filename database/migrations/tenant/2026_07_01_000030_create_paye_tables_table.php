<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paye_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('version_name', 100);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'is_current'], 'paye_current_unique');
        });

        Schema::create('paye_table_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paye_table_id')->constrained()->cascadeOnDelete();
            $table->decimal('threshold', 15, 2);
            $table->decimal('upper_limit', 15, 2)->nullable();
            $table->decimal('rate', 5, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paye_table_bands');
        Schema::dropIfExists('paye_tables');
    }
};
