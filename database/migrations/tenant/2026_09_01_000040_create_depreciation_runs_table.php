<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('run_number', 50);
            $table->string('period', 7)->comment('YYYY-MM');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('status', 20)->default('draft');
            $table->decimal('total_depreciation_amount', 15, 2)->default(0);
            $table->integer('assets_processed')->default(0);
            $table->integer('assets_skipped')->default(0);
            $table->text('skip_reasons')->nullable()->comment('JSON array of {asset_id, reason}');
            $table->foreignId('journal_entry_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'run_number']);
            $table->index(['company_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_runs');
    }
};
