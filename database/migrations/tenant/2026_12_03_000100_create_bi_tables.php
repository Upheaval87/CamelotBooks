<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bi_settings')) return;

        // Scenario / allocation / assumption persistence. `value` holds either
        // a scalar or a JSON-cast array (per-branch expectations, snowballs…).
        Schema::create('bi_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('group_key', 60);          // scenario|allocation|assumptions|pricing
            $table->string('key', 60);                // best_case, bull, payroll, wacc…
            $table->json('value')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['company_id', 'group_key', 'key']);
        });

        // Break-even fixed/variable classification per expense account.
        Schema::create('bi_expense_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id');
            $table->enum('class', ['fixed', 'variable'])->default('variable');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['company_id', 'account_id']);
        });

        // Board Pack action items & owners (compiled / hand-maintained rows).
        Schema::create('bi_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('page', 30)->default('board');
            $table->string('description', 500);
            $table->string('owner', 120)->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Insert-only audit trail for every persisted BI setting change.
        if (!Schema::hasTable('bi_audit_log')) {
            Schema::create('bi_audit_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('action', 60);           // setting.updated | class.updated | action.saved
                $table->string('group_key', 60)->nullable();
                $table->string('key', 60)->nullable();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_audit_log');
        Schema::dropIfExists('bi_actions');
        Schema::dropIfExists('bi_expense_classes');
        Schema::dropIfExists('bi_settings');
    }
};