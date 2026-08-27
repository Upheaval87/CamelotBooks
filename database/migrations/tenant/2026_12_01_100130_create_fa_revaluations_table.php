<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_revaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->date('revaluation_date');
            $table->decimal('previous_value', 15, 2)->default(0);
            $table->decimal('new_value', 15, 2)->default(0);
            $table->decimal('surplus_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_revaluations');
    }
};
