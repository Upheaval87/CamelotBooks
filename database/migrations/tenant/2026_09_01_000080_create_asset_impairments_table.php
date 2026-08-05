<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_impairments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('impairment_date');
            $table->decimal('recoverable_amount', 15, 2);
            $table->decimal('previous_nbv', 15, 2);
            $table->decimal('impairment_amount', 15, 2);
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reversed_impairment_id')->nullable()->constrained('asset_impairments')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->text('memo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_impairments');
    }
};
