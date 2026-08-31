<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting method (chosen at company creation) plus the reporting-view
     * preference that a cash-basis company records when it wants reports to
     * render in cash terms. Both live on the CENTRAL companies row (the
     * tenant-scoped `companies` table is a minimal stub; the wizard, COA and
     * Switch-to-Accrual all read the central row through App\Models\Company).
     *
     * Guarded so it coexists with the (unapplied) 2026_08_19_000010 migration
     * that adds `accounting_method` on its own; whichever runs first wins and
     * the other becomes a no-op.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'accounting_method')) {
                $table->string('accounting_method', 10)->default('accrual')->after('base_currency');
            }
            if (! Schema::hasColumn('companies', 'reporting_preference')) {
                $table->string('reporting_preference', 20)->default('accrual_view')->after('accounting_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'reporting_preference')) {
                $table->dropColumn('reporting_preference');
            }
        });
    }
};
