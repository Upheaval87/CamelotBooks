<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('run_number', 50);
            $table->string('period_label', 100);
            $table->date('pay_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'calculated', 'posted', 'partially_paid', 'fully_paid'])->default('draft');
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_paye', 15, 2)->default(0);
            $table->decimal('total_pension_ee', 15, 2)->default(0);
            $table->decimal('total_pension_er', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net_pay', 15, 2)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('paye_table_id')->nullable()->constrained('paye_tables')->nullOnDelete();
            $table->foreignId('pension_scheme_id')->nullable()->constrained('pension_schemes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'run_number'], 'prun_company_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
