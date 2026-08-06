<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_support_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason', 50)->nullable();

            $table->index(['user_id', 'ended_at']);
            $table->index(['company_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_support_sessions');
    }
};
