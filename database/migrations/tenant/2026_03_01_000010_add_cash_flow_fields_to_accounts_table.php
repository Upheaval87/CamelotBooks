<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('cash_flow_section', 20)->nullable()->after('is_active');
            $table->boolean('is_non_cash')->default(false)->after('cash_flow_section');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['cash_flow_section', 'is_non_cash']);
        });
    }
};
