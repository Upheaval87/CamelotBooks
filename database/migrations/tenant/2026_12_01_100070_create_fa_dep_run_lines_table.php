<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_dep_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('fa_dep_runs')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->foreignId('dep_book_id')->constrained('fa_dep_books');
            $table->string('book_type', 10);
            $table->decimal('opening_nbv', 15, 2)->default(0);
            $table->decimal('depreciation_amount', 15, 2)->default(0);
            $table->decimal('closing_nbv', 15, 2)->default(0);
            $table->string('status', 20)->default('posted');
            $table->text('skip_reason')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'asset_id']);
            $table->index(['company_id', 'run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_dep_run_lines');
    }
};
