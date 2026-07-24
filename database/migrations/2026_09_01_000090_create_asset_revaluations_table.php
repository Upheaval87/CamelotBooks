<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_revaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('revaluation_date');
            $table->decimal('previous_nbv', 15, 2);
            $table->decimal('fair_value', 15, 2);
            $table->decimal('surplus_amount', 15, 2)->comment('Positive = increase, negative = decrease');
            $table->decimal('existing_surplus_offset', 15, 2)->default(0)->comment('Amount offset against existing surplus for decreases');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->text('memo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_revaluations');
    }
};
