<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_warranty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->string('provider', 255);
            $table->string('warranty_number', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('terms')->nullable();
            $table->string('contact_info', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['asset_id', 'status']);
            $table->index(['asset_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_warranty');
    }
};
