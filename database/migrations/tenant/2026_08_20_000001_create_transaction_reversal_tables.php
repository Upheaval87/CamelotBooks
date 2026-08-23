<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_reversal_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('reference_number', 30);
            $t->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $t->string('original_transaction_type');
            $t->unsignedBigInteger('original_transaction_id');
            $t->foreignId('requested_by')->constrained('users');
            $t->date('request_date');
            $t->date('reversal_date');
            $t->enum('reversal_method', ['full', 'partial'])->default('full');
            $t->decimal('partial_amount', 15, 2)->nullable();
            $t->text('reason');
            $t->enum('status', [
                'pending_authorization', 'approved', 'rejected',
                'needs_clarification', 'cancelled', 'reversed',
            ])->default('pending_authorization');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_date')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
            $t->unique(['company_id', 'reference_number']);
        });

        Schema::create('transaction_reversals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reversal_request_id')->constrained('transaction_reversal_requests')->cascadeOnDelete();
            $t->foreignId('original_journal_entry_id')->constrained('journal_entries');
            $t->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries');
            $t->string('reversal_number', 30);
            $t->date('reversal_date');
            $t->decimal('amount', 15, 2);
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->unique(['company_id', 'reversal_number']);
        });

        Schema::create('reversal_authorization_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reversal_request_id')->constrained('transaction_reversal_requests')->cascadeOnDelete();
            $t->unsignedInteger('approval_level')->default(1);
            $t->foreignId('assigned_to')->constrained('users');
            $t->enum('status', ['pending', 'approved', 'rejected', 'needs_clarification'])->default('pending');
            $t->text('comments')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_date')->nullable();
            $t->timestamps();
        });

        Schema::create('reversal_authorization_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('transaction_type')->nullable();
            $t->decimal('minimum_amount', 15, 2)->default(0);
            $t->decimal('maximum_amount', 15, 2)->nullable();
            $t->unsignedInteger('required_approvals')->default(1);
            $t->string('approver_role');
            $t->foreignId('branch_id')->nullable()->constrained('branches');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('reversal_approval_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reversal_request_id')->constrained('transaction_reversal_requests')->cascadeOnDelete();
            $t->string('action');
            $t->foreignId('performed_by')->constrained('users');
            $t->text('remarks')->nullable();
            $t->timestamp('date_time');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversal_approval_history');
        Schema::dropIfExists('reversal_authorization_rules');
        Schema::dropIfExists('reversal_authorization_requests');
        Schema::dropIfExists('transaction_reversals');
        Schema::dropIfExists('transaction_reversal_requests');
    }
};
