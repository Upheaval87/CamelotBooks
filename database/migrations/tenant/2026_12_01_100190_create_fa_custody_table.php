<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_custody', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->string('from_custodian', 255)->nullable();
            $table->string('to_custodian', 255);
            $table->date('handover_date');
            $table->text('reason')->nullable();
            $table->text('condition_notes')->nullable();
            $table->foreignId('handed_by')->nullable()->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['asset_id', 'handover_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_custody');
    }
};
