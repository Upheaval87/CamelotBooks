<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System-driven obligation transitions (e.g. the engine auto-advancing
 * OPEN → CALCULATING when the first tax transaction posts) audit-log with no
 * actor. Those writes pass a null user, but tax_audit_trail.user_id was a
 * non-nullable FK to users → the audit insert failed with an FK violation.
 *
 * This is an additive, guarded change: it only acts when the table + column
 * exist, so it repairs live tenant DBs and is a no-op on fresh installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_audit_trail')) {
            return;
        }

        Schema::table('tax_audit_trail', function (Blueprint $table) {
            if (Schema::hasColumn('tax_audit_trail', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tax_audit_trail')) {
            return;
        }

        Schema::table('tax_audit_trail', function (Blueprint $table) {
            if (Schema::hasColumn('tax_audit_trail', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            }
        });
    }
};