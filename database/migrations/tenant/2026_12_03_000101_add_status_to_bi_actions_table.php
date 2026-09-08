<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bi_actions')) return;
        if (Schema::hasColumn('bi_actions', 'status')) return;

        Schema::table('bi_actions', function (Blueprint $table) {
            $table->string('status', 30)->default('open')->after('due_date');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bi_actions')) return;
        if (!Schema::hasColumn('bi_actions', 'status')) return;

        Schema::table('bi_actions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};