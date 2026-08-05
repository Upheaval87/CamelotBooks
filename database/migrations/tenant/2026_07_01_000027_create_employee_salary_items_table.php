<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained('employee_salary_structures')->cascadeOnDelete();
            $table->foreignId('company_allowance_id')->nullable()->constrained('company_allowances')->nullOnDelete();
            $table->string('type', 20);
            $table->string('name', 100);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_items');
    }
};
