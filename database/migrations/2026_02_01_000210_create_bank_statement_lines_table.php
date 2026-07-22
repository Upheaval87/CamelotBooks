<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('bank_statement_imports')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->date('transaction_date');
            $table->string('description', 500);
            $table->string('reference', 100)->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance', 15, 2)->nullable();
            $table->boolean('is_matched')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index('import_id');
            $table->index(['bank_account_id', 'is_matched']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
