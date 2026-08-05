<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_depreciation_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depreciation_run_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('period_number');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->decimal('opening_nbv', 15, 2);
            $table->decimal('depreciation_charge', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('closing_nbv', 15, 2);
            $table->decimal('units_used', 12, 2)->nullable()->comment('For UOP periods');

            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['asset_id', 'asset_depreciation_book_id', 'period_number'], 'dse_asset_book_period_idx');
            $table->index(['is_posted', 'period_end_date'], 'dse_posted_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_schedule_entries');
    }
};
