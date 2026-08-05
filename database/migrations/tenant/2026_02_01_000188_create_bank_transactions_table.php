<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->foreignId('journal_entry_id')->constrained('journal_entries');
            $table->string('type', 20);
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('date');
            $table->string('description', 500);
            $table->string('reference', 100)->nullable();
            $table->decimal('amount', 15, 2);
            $table->foreignId('linked_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('bank_reconciliation_id')->nullable()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'bank_account_id', 'date']);
            $table->index(['company_id', 'bank_account_id', 'is_reconciled']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
