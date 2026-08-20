<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('company_id');
            $table->string('full_name', 255);
            $table->string('relationship', 50);
            $table->string('phone', 50)->nullable();
            $table->decimal('pct', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_beneficiaries');
    }
};
