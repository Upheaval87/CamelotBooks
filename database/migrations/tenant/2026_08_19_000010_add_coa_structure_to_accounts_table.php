<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->after('sub_type');
            $table->integer('level')->default(1)->after('is_group');
            $table->boolean('allow_posting')->default(true)->after('level');
            $table->boolean('is_system_account')->default(false)->after('allow_posting');
            $table->string('normal_balance', 10)->nullable()->after('is_system_account');
            $table->string('posting_behaviour', 20)->default('both')->after('normal_balance');
            $table->boolean('allow_adjustments')->default(true)->after('posting_behaviour');
            $table->string('legacy_code', 50)->nullable()->after('allow_adjustments');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'is_group', 'level', 'allow_posting', 'is_system_account',
                'normal_balance', 'posting_behaviour', 'allow_adjustments', 'legacy_code',
            ]);
        });
    }
};
