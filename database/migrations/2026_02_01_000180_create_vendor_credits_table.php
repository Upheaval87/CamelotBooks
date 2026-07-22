<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->nullOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('credit_note_number', 30);
            $table->date('credit_note_date');
            $table->string('reference', 100)->nullable();
            $table->text('memo')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('amount_applied', 15, 2)->default(0);
            $table->decimal('amount_refunded', 15, 2)->default(0);
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'credit_note_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_credits');
    }
};
