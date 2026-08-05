<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_bank_account')->default(false)->after('is_active');
            $table->string('bank_name', 100)->nullable()->after('is_bank_account');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_routing_number', 50)->nullable()->after('bank_account_number');
            $table->string('bank_branch', 100)->nullable()->after('bank_routing_number');

            $table->index(['is_bank_account']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['is_bank_account']);
            $table->dropColumn([
                'is_bank_account',
                'bank_name',
                'bank_account_number',
                'bank_routing_number',
                'bank_branch',
            ]);
        });
    }
};
