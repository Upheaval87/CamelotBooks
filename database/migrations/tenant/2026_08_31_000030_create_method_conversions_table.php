<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One active cash→accrual conversion per company. `status` moves from
     * 'draft' → 'activated' (irreversible). The conversion journal is posted
     * through the existing JournalPostingEngine; `conversion_journal_id`
     * references that journal entry (self-referential link — no FK so the
     * journal can be created indepently first).
     */
    public function up(): void
    {
        Schema::create('method_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('from_method', 10)->default('cash');
            $table->string('to_method', 10)->default('accrual');
            $table->date('cut_off_date')->nullable();
            $table->string('treatment', 50)->default('reclassify');
            $table->unsignedBigInteger('conversion_journal_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('method_conversions');
    }
};
