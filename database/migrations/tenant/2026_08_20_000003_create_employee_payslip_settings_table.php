<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payslip_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->boolean('email_delivery')->default(true);
            $table->boolean('portal_access')->default(true);
            $table->text('custom_email')->nullable();
            $table->text('access_pin')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payslip_settings');
    }
};
