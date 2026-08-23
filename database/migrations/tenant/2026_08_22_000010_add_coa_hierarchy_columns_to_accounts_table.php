<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'is_contra')) {
                $table->boolean('is_contra')->default(false)->after('is_system_account');
            }
            if (!Schema::hasColumn('accounts', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_contra');
            }
            if (!Schema::hasColumn('accounts', 'version')) {
                $table->integer('version')->default(1)->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['is_contra', 'sort_order', 'version']);
        });
    }
};
