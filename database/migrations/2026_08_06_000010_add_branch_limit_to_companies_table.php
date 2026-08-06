<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch-limit enforcement (multi-tenant billing prep).
 *
 * - branch_limit : nullable int. NULL = unlimited (legacy companies from before
 *   this feature are untouched and stay unlimited). 0 = no branches allowed.
 * - branch_count : denormalized count of ACTIVE branches. The authoritative
 *   value is the live count (BranchLimitService::liveCount()); this cached
 *   column is reconciled on every enforcement/usage read so stale rows
 *   self-heal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('branch_limit')->nullable()->after('fiscal_year_start_month');
            $table->unsignedInteger('branch_count')->default(0)->after('branch_limit');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['branch_limit', 'branch_count']);
        });
    }
};
