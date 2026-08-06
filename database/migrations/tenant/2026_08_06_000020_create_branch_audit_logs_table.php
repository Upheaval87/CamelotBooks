<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable record of every branch CREATION.
 *
 * `created_by_user_id` deliberately has NO foreign key to `users`: the tenant
 * users table only holds stub rows copied at migration time, so a hard FK
 * would reject legitimate creates by central users assigned after the tenant
 * was provisioned. The rest of the tenant schema mirrors this constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('created_by_user_id');
            $table->string('created_by_role'); // super_admin | company_manager
            $table->boolean('was_override')->default(false);
            $table->unsignedInteger('branch_limit_at_creation')->nullable();
            $table->unsignedInteger('branch_count_at_creation')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['company_id', 'created_at'], 'bal_company_created_idx');
            $table->index('created_by_user_id', 'bal_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_audit_log');
    }
};
