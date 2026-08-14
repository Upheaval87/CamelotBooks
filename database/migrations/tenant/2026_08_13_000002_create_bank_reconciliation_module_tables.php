<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->foreignId('branch_id')->nullable()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->nullOnDelete();
            $table->string('statement_number', 60)->nullable();
            $table->date('statement_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->string('currency', 10)->default('MWK');
            $table->string('status', 30)->default('draft');
            $table->decimal('statement_balance', 15, 2)->default(0);
            $table->decimal('book_balance', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->foreignId('approved_by')->nullable()->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('completed_by')->nullable()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 500)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'bank_account_id', 'status'], 'rec_company_account_status_idx');
            $table->index(['company_id', 'statement_date'], 'rec_company_date_idx');
        });

        Schema::create('bank_reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->nullable()->constrained('bank_statement_lines')->nullOnDelete();
            $table->foreignId('bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
            $table->string('method', 30)->default('manual');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['reconciliation_id', 'bank_statement_line_id', 'bank_transaction_id'], 'rec_match_unique');
            $table->index(['reconciliation_id', 'bank_statement_line_id'], 'rec_match_line_idx');
            $table->index(['reconciliation_id', 'bank_transaction_id'], 'rec_match_txn_idx');
        });

        Schema::create('bank_reconciliation_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('side', 10);
            $table->string('sign', 10);
            $table->decimal('amount', 15, 2);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['reconciliation_id', 'status'], 'rec_adj_recon_status_idx');
        });

        Schema::create('bank_reconciliation_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->string('action', 60);
            $table->json('details')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at')->nullable();

            $table->index(['reconciliation_id', 'created_at'], 'rec_audit_recon_created_idx');
        });

        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->foreignId('reconciliation_id')->nullable()->after('bank_account_id')->constrained('bank_reconciliations')->nullOnDelete();
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->after('bank_account_id')->constrained('bank_reconciliations')->nullOnDelete();
            $table->string('status', 20)->default('unmatched')->after('is_matched');
            $table->unsignedBigInteger('match_id')->nullable()->after('status');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('reconciliation_status', 20)->nullable()->after('bank_reconciliation_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn('reconciliation_status');
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn(['match_id', 'status', 'reconciliation_id', 'company_id']);
        });

        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reconciliation_id');
        });

        Schema::dropIfExists('bank_reconciliation_audit_logs');
        Schema::dropIfExists('bank_reconciliation_adjustments');
        Schema::dropIfExists('bank_reconciliation_matches');
        Schema::dropIfExists('bank_reconciliations');
    }
};
