<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('failure_reason', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempt_logs');
    }
};
