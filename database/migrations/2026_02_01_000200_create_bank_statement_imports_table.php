<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->string('filename', 255);
            $table->date('statement_date');
            $table->decimal('statement_end_balance', 15, 2);
            $table->unsignedInteger('line_count')->default(0);
            $table->foreignId('imported_by')->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'bank_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_imports');
    }
};
