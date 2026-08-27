<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->string('event_type', 50)->comment('created, activated, depreciated, transferred, disposed, impaired, revalued, verified, custody, maintenance, document, adjustment');
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['asset_id', 'event_type']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_history');
    }
};
