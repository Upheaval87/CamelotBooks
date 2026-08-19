<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend recurring_journal_templates
        Schema::table('recurring_journal_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('recurring_journal_templates', 'reference')) {
                $table->string('reference', 60)->nullable()->after('name');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'description')) {
                $table->text('description')->nullable()->after('memo');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'journal_type')) {
                $table->string('journal_type', 30)->default('standard')->after('frequency');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'currency')) {
                $table->string('currency', 10)->default('MWK')->after('journal_type');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'occurrences')) {
                $table->unsignedInteger('occurrences')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'generation_mode')) {
                $table->string('generation_mode', 30)->default('auto_post')->after('occurrences');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'email_notification')) {
                $table->string('email_notification', 30)->default('none')->after('generation_mode');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'status')) {
                $table->string('status', 20)->default('active')->after('is_active');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'last_generated_at')) {
                $table->timestamp('last_generated_at')->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('last_generated_at');
            }
            if (!Schema::hasColumn('recurring_journal_templates', 'generated_count')) {
                $table->unsignedInteger('generated_count')->default(0)->after('failed_count');
            }
        });

        // Extend recurring_journal_template_lines — add company_id, description, line_type
        Schema::table('recurring_journal_template_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('recurring_journal_template_lines', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('recurring_journal_template_lines', 'description')) {
                $table->string('description', 500)->nullable()->after('account_id');
            }
            if (!Schema::hasColumn('recurring_journal_template_lines', 'line_type')) {
                $table->string('line_type', 20)->nullable()->after('description');
            }
            if (!Schema::hasColumn('recurring_journal_template_lines', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            }
        });

        // New: recurring_journal_runs (generated journals tracking)
        if (!Schema::hasTable('recurring_journal_runs')) {
            Schema::create('recurring_journal_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('recurring_journal_template_id')->constrained()->cascadeOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
                $table->date('run_date');
                $table->string('reference', 60)->nullable();
                $table->string('status', 20)->default('draft'); // draft, pending_approval, posted, reversed, failed
                $table->decimal('total_debit', 15, 2)->default(0);
                $table->decimal('total_credit', 15, 2)->default(0);
                $table->text('failure_reason')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->boolean('is_test')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['recurring_journal_template_id', 'run_date']);
            });
        }

        // New: recurring_journal_history (immutable audit trail)
        if (!Schema::hasTable('recurring_journal_history')) {
            Schema::create('recurring_journal_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('recurring_journal_template_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('recurring_journal_run_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 50); // created, modified, generated, auto_posted, failed, reversed, approved, rejected, schedule_changed
                $table->text('description');
                $table->string('actor_type', 20)->default('user'); // user, engine
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamp('happened_at');
                $table->timestamps();

                $table->index(['company_id', 'action']);
                $table->index(['recurring_journal_template_id']);
            });
        }

        // New: recurring_journal_settings (per-company)
        if (!Schema::hasTable('recurring_journal_settings')) {
            Schema::create('recurring_journal_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('numbering_pattern', 60)->default('RJV-{yyyy}-{seq:6}');
                $table->boolean('approval_required')->default(false);
                $table->decimal('approval_threshold', 15, 2)->default(0);
                $table->string('auto_post_rules', 100)->nullable(); // JSON: e.g. {"exclude_types":["accrual"]}
                $table->string('notification_email', 30)->default('after_posting');
                $table->boolean('block_locked_periods')->default(true);
                $table->foreignId('default_suspense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->timestamps();

                $table->unique('company_id');
            });
        }

        // Seed numbering sequences for recurring journals (guarded — table created by a later migration)
        if (Schema::hasTable('numbering_sequences')) {
            DB::table('numbering_sequences')->insertOrIgnore([
                ['document_type' => 'rj_template', 'prefix' => 'RJ-', 'padding_width' => 4, 'next_number' => 1, 'reset_policy' => 'never', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['document_type' => 'rj_generated', 'prefix' => 'RJV-', 'padding_width' => 6, 'next_number' => 1, 'reset_policy' => 'annually', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journal_history');
        Schema::dropIfExists('recurring_journal_runs');
        Schema::dropIfExists('recurring_journal_settings');

        Schema::table('recurring_journal_template_lines', function (Blueprint $table) {
            if (Schema::hasColumn('recurring_journal_template_lines', 'cost_center_id')) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            }
        });

        Schema::table('recurring_journal_templates', function (Blueprint $table) {
            $columns = ['reference', 'description', 'journal_type', 'currency', 'occurrences', 'generation_mode', 'email_notification', 'status', 'total_amount', 'last_generated_at', 'failed_count', 'generated_count'];
            $existing = array_filter($columns, fn($c) => Schema::hasColumn('recurring_journal_templates', $c));
            if ($existing) $table->dropColumn($existing);
        });
    }
};
