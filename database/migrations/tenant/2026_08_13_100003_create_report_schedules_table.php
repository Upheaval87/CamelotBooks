<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('report_key', 50);  // fin.income|fin.position|fin.cashflow|fin.ar-aging|fin.ap-aging
            $table->json('filters');            // saved filter set
            $table->string('frequency', 20);    // DAILY|WEEKLY|MONTHLY
            $table->json('recipients');         // list of email addresses
            $table->string('format', 10);       // PDF|EXCEL
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status', 20)->nullable(); // SUCCESS|FAILED
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
