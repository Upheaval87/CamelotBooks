<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_verification_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verification_id')->constrained('fa_verifications')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->boolean('is_verified')->default(false);
            $table->string('condition', 30)->nullable()->comment('good, fair, poor, missing');
            $table->string('actual_location', 255)->nullable();
            $table->string('actual_custodian', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['verification_id', 'asset_id']);
            $table->index(['company_id', 'verification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_verification_lines');
    }
};
