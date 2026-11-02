<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email dispatch subsystem (§2.1 / §2.2) — nullable-safe, additive only.
 *
 * `quotations` already carries `emailed_at` + `emailed_to` from the spec-column
 * migration, so only the two new dispatch columns are added here. The
 * `email_jobs` table backs the four-stage progress pipeline. Every step is
 * guarded so it is safe on fresh installs and on already-provisioned tenant
 * databases (per the phase-55 rule: never edit an applied migration — add a
 * new guarded one).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'email_status')) {
                $table->enum('email_status', ['sent', 'failed'])->nullable()->after('emailed_to');
            }
            if (!Schema::hasColumn('quotations', 'fail_reason')) {
                $table->string('fail_reason', 255)->nullable()->after('email_status');
            }
        });

        if (!Schema::hasTable('email_jobs')) {
            Schema::create('email_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->morphs('quotable');
                $table->string('recipient', 255);
                $table->enum('stage', [
                    'preparing',
                    'uploading',
                    'delivering',
                    'recording',
                ])->default('preparing');
                // Exactly the §2.2 enum. §4.4 cancellation is recorded on
                // `cancel_requested_at` (see QuotationEmailDispatchService::cancel)
                // and surfaced to the client as a derived "cancelled" status by the
                // status endpoint, so the stored enum stays literal.
                $table->enum('status', ['running', 'succeeded', 'failed'])
                    ->default('running');
                $table->string('error_code', 100)->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedTinyInteger('attempts')->default(1);
                $table->unsignedBigInteger('dispatched_by')->nullable();
                $table->timestamp('cancel_requested_at')->nullable();
                $table->timestamps();

                $table->index(['quotable_type', 'quotable_id', 'status'], 'email_jobs_quotable_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach (['email_status', 'fail_reason'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('email_jobs');
    }
};