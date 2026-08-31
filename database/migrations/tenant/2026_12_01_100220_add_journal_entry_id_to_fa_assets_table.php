<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fa_assets', 'journal_entry_id')) {
            Schema::table('fa_assets', function (Blueprint $table) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('disposal_account_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fa_assets', 'journal_entry_id')) {
            Schema::table('fa_assets', function (Blueprint $table) {
                $table->dropColumn('journal_entry_id');
            });
        }
    }
};
