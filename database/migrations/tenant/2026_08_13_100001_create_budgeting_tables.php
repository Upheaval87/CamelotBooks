<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy tables if they exist (empty, replaced by new schema)
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');

        // ── budgets ───────────────────────────────────────────────
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('type', 30)->default('operating');          // operating|capital|project|department|cash_flow
            $table->unsignedBigInteger('fiscal_year_id');
            $table->string('period', 20)->default('annual');           // annual|quarterly|monthly|custom
            $table->string('department')->nullable();                   // free-text (no Department model)
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('project')->nullable();                     // free-text (no Project model)
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('status', 30)->default('draft');            // draft|pending_approval|approved|locked|rejected
            $table->string('currency', 10)->default('MWK');
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->unsignedBigInteger('prepared_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('approval_chain')->nullable();                // [{level, label, approver_id, status, comment, at}]
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index('fiscal_year_id');
        });

        // ── budget_lines ──────────────────────────────────────────
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('company_id');
            $table->string('line_type', 20);                          // income|expense
            $table->unsignedBigInteger('account_id');
            $table->decimal('annual_amount', 15, 2)->default(0);
            $table->decimal('monthly_amount', 15, 2)->default(0);
            $table->string('distribution', 20)->default('even');      // even|seasonal|custom
            $table->json('distribution_config')->nullable();           // {months: [pct, ...]} for seasonal/custom
            $table->string('department')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('project')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->timestamps();

            $table->index('budget_id');
            $table->index('company_id');
            $table->index(['company_id', 'account_id']);
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });

        // ── budget_templates ──────────────────────────────────────
        Schema::create('budget_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('basis', 30)->default('blank');            // blank|prior_actuals|standard|zero_based
            $table->unsignedInteger('lines_count')->default(0);
            $table->json('template_data')->nullable();                 // serialized lines for reuse
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('company_id');
        });

        // ── budget_adjustments ────────────────────────────────────
        Schema::create('budget_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('budget_line_id')->nullable();
            $table->string('code', 30)->unique();                     // ADJ-xxxx
            $table->string('type', 20);                               // increase|reduce|transfer
            $table->unsignedBigInteger('from_line_id')->nullable();   // for transfers
            $table->unsignedBigInteger('to_line_id')->nullable();     // for transfers
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('reason');
            $table->string('status', 30)->default('pending');         // pending|approved|rejected
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comment')->nullable();
            $table->decimal('original_amount', 15, 2)->nullable();    // versioned — original value before adjustment
            $table->timestamps();

            $table->index('company_id');
            $table->index('budget_id');
            $table->index(['company_id', 'status']);
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });

        // ── budget_alert_rules ────────────────────────────────────
        Schema::create('budget_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('rule_type', 30);                          // threshold|unusual|low_balance
            $table->decimal('warn_threshold', 5, 2)->default(85);     // percentage
            $table->decimal('exceed_threshold', 5, 2)->default(100);  // percentage
            $table->decimal('unusual_multiplier', 5, 2)->default(1.25);
            $table->decimal('low_balance_threshold', 5, 2)->default(10);
            $table->string('scope', 30)->default('budget');           // budget|department|line
            $table->json('channels')->nullable();                     // ["email","sms","system"]
            $table->json('recipient_ids')->nullable();                 // [user_ids]
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'is_active']);
        });

        // ── budget_alerts (fired alerts) ──────────────────────────
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->unsignedBigInteger('budget_id')->nullable();
            $table->unsignedBigInteger('budget_line_id')->nullable();
            $table->string('severity', 20);                           // exceeded|nearing|unusual
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'is_read']);
            $table->foreign('rule_id')->references('id')->on('budget_alert_rules')->onDelete('set null');
        });

        // ── budget_audit_logs ─────────────────────────────────────
        Schema::create('budget_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('user_id');
            $table->string('action', 50);                             // created|updated|submitted|approved|rejected|locked|unlocked|adjustment|transfer
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_audit_logs');
        Schema::dropIfExists('budget_alerts');
        Schema::dropIfExists('budget_alert_rules');
        Schema::dropIfExists('budget_adjustments');
        Schema::dropIfExists('budget_templates');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
