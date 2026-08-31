<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-period posting basis. A cash-basis company's periods are 'cash'
     * until the Switch-to-Accrual cut-off, then ('accrual') thereafter.
     */
    public function up(): void
    {
        Schema::table('accounting_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_periods', 'basis')) {
                $table->string('basis', 10)->default('accrual')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounting_periods', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_periods', 'basis')) {
                $table->dropColumn('basis');
            }
        });
    }
};
