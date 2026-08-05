<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('journal_number');
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_adjusting_entry')->default(false);
            $table->string('source_module')->nullable();
            $table->foreignId('linked_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('recurring_template_id')->nullable()->constrained('recurring_journal_templates')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'journal_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
