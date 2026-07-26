<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_payments', 'account_name')) {
                $table->string('account_name')->nullable()->after('reference_number');
            }
            if (!Schema::hasColumn('pos_payments', 'institution')) {
                $table->string('institution')->nullable()->after('account_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_payments', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'institution']);
        });
    }
};
