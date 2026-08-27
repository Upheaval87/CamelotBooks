<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_insurance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->string('policy_number', 100);
            $table->string('provider', 255);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('coverage_amount', 15, 2)->default(0);
            $table->decimal('annual_premium', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['asset_id', 'status']);
            $table->index(['asset_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_insurance');
    }
};
