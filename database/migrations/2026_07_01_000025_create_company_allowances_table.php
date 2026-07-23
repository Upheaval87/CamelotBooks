<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->enum('type', ['allowance', 'deduction'])->default('allowance');
            $table->foreignId('gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'allowance_company_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_allowances');
    }
};
