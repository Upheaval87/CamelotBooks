<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company-side request to raise their branch_limit by a number of branches.
 *
 * Lifecycle: pending_review -> quoted -> awaiting_payment -> approved
 *             |-> rejected |-> expired |-> cancelled
 *
 * `requested_by_user_id` deliberately has NO foreign key to `users`: the tenant
 * users table only holds stub rows copied at migration time, so a hard FK
 * would reject requests by central users assigned after provisioning. This
 * mirrors the branch_audit_log constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('branch_name');
            $table->string('branch_code')->nullable();
            $table->string('branch_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->unsignedInteger('requested_quantity')->default(1);
            $table->string('status')->default('pending_review');
            $table->text('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('requested_by_user_id');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'br_company_status_idx');
            $table->index(['company_id', 'created_at'], 'br_company_created_idx');
            $table->index('requested_by_user_id', 'br_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_requests');
    }
};
