<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_dep_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('run_number', 50);
            $table->string('period', 30)->comment('e.g. 2026-08');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('book_type', 10)->default('financial');
            $table->integer('asset_count')->default(0);
            $table->decimal('total_depreciation', 15, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('run_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('run_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'run_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_dep_runs');
    }
};
