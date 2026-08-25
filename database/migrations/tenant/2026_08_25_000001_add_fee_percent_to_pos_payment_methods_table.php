<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_payment_methods') && !Schema::hasColumn('pos_payment_methods', 'fee_percent')) {
            Schema::table('pos_payment_methods', function (Blueprint $table) {
                $table->decimal('fee_percent', 5, 2)->default(0)->after('requires_reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_payment_methods', 'fee_percent')) {
            Schema::table('pos_payment_methods', function (Blueprint $table) {
                $table->dropColumn('fee_percent');
            });
        }
    }
};
