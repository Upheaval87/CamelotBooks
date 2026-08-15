<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'department')) {
                $table->string('department', 100)->nullable();
            }
            if (!Schema::hasColumn('expenses', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'payment_status')) {
                $table->string('payment_status', 20)->default('unpaid');
            }
            if (!Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method', 40)->nullable();
            }
            if (!Schema::hasColumn('expenses', 'payment_account_id')) {
                $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'payment_date')) {
                $table->date('payment_date')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'payment_reference')) {
                $table->string('payment_reference', 100)->nullable();
            }
            if (!Schema::hasColumn('expenses', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('expenses', 'tax_total')) {
                $table->decimal('tax_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('expenses', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('expenses', 'claim_id')) {
                $table->foreignId('claim_id')->nullable()->constrained('expense_claims')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'returned_by')) {
                $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'returned_at')) {
                $table->timestamp('returned_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'return_reason')) {
                $table->text('return_reason')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'budget_reason')) {
                $table->text('budget_reason')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'budget_approver_id')) {
                $table->foreignId('budget_approver_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'budget_approved_at')) {
                $table->timestamp('budget_approved_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'budget_check')) {
                $table->text('budget_check')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'budget_check_amount')) {
                $table->decimal('budget_check_amount', 15, 2)->nullable();
            }
        });

        Schema::table('expense_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_lines', 'department')) {
                $table->string('department', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_lines', function (Blueprint $table) {
            $table->dropColumn('department');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $columns = [
                'category_id', 'department', 'employee_id', 'payment_status', 'payment_method',
                'payment_account_id', 'payment_date', 'payment_reference', 'subtotal', 'tax_total',
                'discount', 'claim_id', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at',
                'rejected_by', 'rejected_at', 'rejection_reason', 'returned_by', 'returned_at',
                'return_reason', 'budget_reason', 'budget_approver_id', 'budget_approved_at',
                'budget_check', 'budget_check_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
