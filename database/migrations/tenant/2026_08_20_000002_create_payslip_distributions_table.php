<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('payslip_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->string('channel', 30)->default('email');
            $table->string('status', 30)->default('pending');
            $table->string('email_address')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['payslip_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_distributions');
    }
};
