<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('payroll_run_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->string('payslip_number', 50);
            $table->string('status', 30)->default('draft');
            $table->decimal('gross_pay', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->json('earnings')->nullable();
            $table->json('deductions')->nullable();
            $table->json('employer_contributions')->nullable();
            $table->json('ytd_totals')->nullable();
            $table->text('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
