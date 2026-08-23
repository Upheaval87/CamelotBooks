<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── §0 DDL — Tax Module Schema ──────────────────────────────────
        if (Schema::hasTable('tax_types')) {
            return;
        }

        Schema::create('tax_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('category', 30); // VAT|WHT|PAYE|FBT|CORPORATE|PRESUMPTIVE|OTHER
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'active']);
        });

        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('country', 100);
            $table->string('authority', 200);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->nullable()->constrained('tax_jurisdictions')->nullOnDelete();
            $table->string('treatment', 30)->default('STANDARD'); // STANDARD|ZERO_RATED|EXEMPT|DEDUCTED|CHARGED|REVERSE_CHARGE
            $table->string('price_basis', 15)->default('EXCLUSIVE'); // EXCLUSIVE|INCLUSIVE
            $table->string('rounding_mode', 15)->default('HALF_UP'); // HALF_UP|HALF_DOWN|HALF_EVEN
            $table->string('rounding_level', 10)->default('LINE'); // LINE|DOCUMENT
            $table->foreignId('gl_output_acct')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('gl_input_acct')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('gl_payable_acct')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'tax_type_id']);
            $table->index(['company_id', 'active']);
        });

        Schema::create('tax_code_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_code_id')->constrained('tax_codes')->cascadeOnDelete();
            $table->decimal('rate_pct', 8, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['tax_code_id', 'effective_from']);
            $table->index(['tax_code_id', 'effective_from', 'effective_to'], 'tcr_code_eff_idx');
        });

        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('reason');
            $table->string('scope', 15); // SALES|PURCHASES|BOTH
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('tax_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity_kind', 20); // COMPANY|CUSTOMER|SUPPLIER
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('jurisdiction_id')->constrained('tax_jurisdictions')->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->string('reg_number', 50);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 15)->default('active');
            $table->timestamps();

            $table->index(['company_id', 'entity_kind', 'entity_id'], 'tax_reg_entity_idx');
            $table->index(['tax_type_id', 'jurisdiction_id'], 'tax_reg_type_jur_idx');
        });

        Schema::create('tax_recognition_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->string('basis', 15); // INVOICE|CASH|PAYMENT|ACCRUAL
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tax_type_id']);
        });

        Schema::create('tax_apportionment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->nullable()->constrained('tax_jurisdictions')->nullOnDelete();
            $table->string('method', 25); // TURNOVER_RATIO|DIRECT_ATTRIBUTION
            $table->decimal('recoverable_pct', 6, 3)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'tax_type_id', 'jurisdiction_id'], 'tax_appt_type_jur_idx');
        });

        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('OPEN'); // OPEN|IN_PREPARATION|SUBMITTED|CLOSED|AMENDED
            $table->date('filing_due_date');
            $table->date('filed_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('reference', 50)->nullable();
            $table->boolean('locked')->default(false);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'tax_type_id', 'label']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->foreignId('tax_code_id')->constrained('tax_codes')->cascadeOnDelete();
            $table->decimal('rate_pct', 8, 4);
            $table->string('side', 25); // OUTPUT|INPUT|WHT|PAYE|ADJUST|REVERSE_CHARGE_OUT|REVERSE_CHARGE_IN
            $table->string('source_kind', 30); // SALES_INVOICE|PURCHASE_BILL|EXPENSE|PAYROLL_RUN|BANK_TXN|ADJUSTMENT|MANUAL
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->foreignId('exemption_id')->nullable()->constrained('tax_exemptions')->nullOnDelete();
            $table->string('exemption_reason', 255)->nullable();
            $table->decimal('apportionment_pct', 6, 3)->nullable();
            $table->decimal('recoverable_tax_amount', 15, 2)->nullable();
            $table->foreignId('jurisdiction_id')->nullable()->constrained('tax_jurisdictions')->nullOnDelete();
            $table->foreignId('gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('recognition_basis', 15); // snapshot: INVOICE|CASH|PAYMENT|ACCRUAL
            $table->timestamp('recognized_at')->nullable();
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reverses_transaction_id')->nullable()->constrained('tax_transactions')->nullOnDelete();
            $table->string('status', 15)->default('POSTED'); // POSTED|UNPOSTED
            $table->timestamps();

            $table->index(['company_id', 'period_id', 'side'], 'tax_txn_period_side_idx');
            $table->index(['company_id', 'source_kind', 'source_id'], 'tax_txn_source_idx');
            $table->index(['company_id', 'tax_code_id']);
        });

        Schema::create('tax_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('direction', 10); // ADD|REDUCE
            $table->text('reason');
            $table->string('status', 15)->default('PENDING'); // PENDING|APPROVED|REJECTED
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'period_id', 'status'], 'tax_adj_period_stat_idx');
        });

        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->foreignId('bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('payment_ref', 30)->unique();
            $table->string('receipt_number', 50)->nullable();
            $table->string('authority', 200)->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->string('status', 15)->default('PENDING'); // PENDING|PAID
            $table->timestamps();

            $table->index(['company_id', 'tax_type_id', 'status'], 'tax_pay_type_stat_idx');
            $table->index(['company_id', 'period_id']);
        });

        Schema::create('wht_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('cert_number', 30);
            $table->foreignId('supplier_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('tax_code_id')->constrained('tax_codes')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->decimal('gross', 15, 2);
            $table->decimal('wht_amount', 15, 2);
            $table->decimal('rate_pct', 8, 4);
            $table->string('status', 15)->default('DRAFT'); // DRAFT|ISSUED
            $table->date('issued_date')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'cert_number']);
            $table->index(['company_id', 'supplier_id']);
        });

        Schema::create('tax_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->string('status', 15)->default('DRAFT'); // DRAFT|APPROVED|FILED
            $table->decimal('output_tax', 15, 2)->default(0);
            $table->decimal('input_tax', 15, 2)->default(0);
            $table->decimal('adjustments', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2)->default(0);
            $table->date('filed_date')->nullable();
            $table->string('reference', 50)->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'tax_type_id', 'period_id']);
        });

        Schema::create('tax_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('tax_returns')->cascadeOnDelete();
            $table->string('section', 15); // OUTPUT|INPUT|ADJUST|TOTAL
            $table->string('label');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('drill_query')->nullable(); // JSON filter params for drill-down
            $table->timestamps();

            $table->index(['return_id', 'section']);
        });

        Schema::create('tax_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('acted_at');
            $table->string('entity_kind', 30); // tax_code|tax_rate|tax_period|tax_return|tax_adjustment|tax_payment|wht_certificate|recognition_rule|apportionment_rule|tax_transaction
            $table->unsignedBigInteger('entity_id');
            $table->string('field', 50)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('approval', 15)->nullable(); // PENDING|APPROVED|REJECTED|SYSTEM
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entity_kind', 'entity_id'], 'tax_audit_entity_idx');
            $table->index(['company_id', 'acted_at'], 'tax_audit_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_audit_trail');
        Schema::dropIfExists('tax_return_lines');
        Schema::dropIfExists('tax_returns');
        Schema::dropIfExists('wht_certificates');
        Schema::dropIfExists('tax_payments');
        Schema::dropIfExists('tax_adjustments');
        Schema::dropIfExists('tax_transactions');
        Schema::dropIfExists('tax_periods');
        Schema::dropIfExists('tax_apportionment_rules');
        Schema::dropIfExists('tax_recognition_rules');
        Schema::dropIfExists('tax_registrations');
        Schema::dropIfExists('tax_exemptions');
        Schema::dropIfExists('tax_code_rates');
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('tax_jurisdictions');
        Schema::dropIfExists('tax_types');
    }
};
