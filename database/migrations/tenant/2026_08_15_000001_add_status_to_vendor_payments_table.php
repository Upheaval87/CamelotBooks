<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendor_payments', 'status')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->string('status', 20)->default('draft')->after('payment_number');
            });

            \Illuminate\Support\Facades\DB::table('vendor_payments')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'posted']);

            \Illuminate\Support\Facades\DB::table('vendor_payments')
                ->whereNull('journal_entry_id')
                ->update(['status' => 'draft']);
        }

        if (!Schema::hasColumn('vendor_payments', 'rejection_reason')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->string('rejection_reason', 1000)->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendor_payments', 'rejection_reason')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }

        if (Schema::hasColumn('vendor_payments', 'status')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
