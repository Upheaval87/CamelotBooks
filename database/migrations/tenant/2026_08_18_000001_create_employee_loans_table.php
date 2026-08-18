<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('loan_number', 50);
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->decimal('monthly_deduction', 15, 2)->default(0);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'loan_number'], 'eloan_company_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
