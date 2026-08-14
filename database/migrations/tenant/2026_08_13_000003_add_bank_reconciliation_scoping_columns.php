<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repairs tenant DBs that applied 2026_08_13_000002 before its
     * bank_statement_lines/imports + bank_transactions scoping columns were added.
     * Guarded with hasColumn so fresh installs (where 000002 already includes
     * these columns) run this as a no-op.
     */
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_statement_lines', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('import_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('bank_statement_lines', 'reconciliation_id')) {
                $table->foreignId('reconciliation_id')->nullable()->after('bank_account_id')->constrained('bank_reconciliations')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_statement_lines', 'status')) {
                $table->string('status', 20)->default('unmatched')->after('is_matched');
            }
            if (!Schema::hasColumn('bank_statement_lines', 'match_id')) {
                $table->unsignedBigInteger('match_id')->nullable()->after('status');
            }
        });

        Schema::table('bank_statement_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_statement_imports', 'reconciliation_id')) {
                $table->foreignId('reconciliation_id')->nullable()->after('bank_account_id')->constrained('bank_reconciliations')->nullOnDelete();
            }
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transactions', 'reconciliation_status')) {
                $table->string('reconciliation_status', 20)->nullable()->after('bank_reconciliation_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_transactions', 'reconciliation_status')) {
                $table->dropColumn('reconciliation_status');
            }
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            foreach (['match_id', 'status', 'reconciliation_id', 'company_id'] as $column) {
                if (Schema::hasColumn('bank_statement_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('bank_statement_imports', function (Blueprint $table) {
            if (Schema::hasColumn('bank_statement_imports', 'reconciliation_id')) {
                $table->dropConstrainedForeignId('reconciliation_id');
            }
        });
    }
};
