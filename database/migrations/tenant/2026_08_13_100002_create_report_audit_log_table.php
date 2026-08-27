<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->timestamp('acted_at');
            $table->string('report_key', 50);
            $table->string('action', 20);
            $table->json('filters');
            $table->string('output_format', 20)->nullable();
            $table->string('recipient')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_audit_log');
    }
};
