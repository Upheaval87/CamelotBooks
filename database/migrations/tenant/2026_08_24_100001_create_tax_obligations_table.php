<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tax Obligations lifecycle — single source of truth for the status of each
 * (company, tax_type, period) obligation.
 *
 * Priority: the status here DRIVES every cross-entity status observable
 * elsewhere (tax_periods.status, tax_returns.status, tax_payments.status are
 * kept in lockstep with this row). Transition gates (see TaxObligationService)
 * must consult obligation.status, not the child entity statuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('tax_periods')->cascadeOnDelete();

            // Upstream lifecycle (single source of truth — do not drift literals).
            $table->string('status', 32)->default('OPEN');
            //     OPEN → CALCULATING → READY_TO_RECONCILE → RECONCILED
            //     → RETURN_DRAFTED → RETURN_APPROVED → FILED → PAID → CLOSED
            //     REJECTED side-state reachable from RETURN_DRAFTED / RETURN_APPROVED.

            // Blocking reasons surfaced on the Tax Obligations dashboard.
            $table->string('blocked_reason', 255)->nullable();

            // Reconciliation gate: variance must be zero OR explicitly waived.
            $table->boolean('variance_waived')->default(false);
            $table->string('variance_waived_reason', 255)->nullable();
            $table->unsignedBigInteger('variance_waived_by')->nullable();
            $table->timestamp('variance_waived_at')->nullable();

            // Payment gate: an explicit nil / refund declaration satisfies the
            // FILED → PAID transition when no payment is required.
            $table->boolean('nil_or_refund_flag')->default(false);
            $table->string('nil_or_refund_reason', 255)->nullable();

            // Explicit sign-off that closes a PAID obligation.
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'tax_type_id', 'period_id'], 'tax_oblig_company_type_period_uniq');
            $table->index(['company_id', 'status'], 'tax_oblig_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_obligations');
    }
};