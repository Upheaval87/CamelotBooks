<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_periods')) return;

        DB::table('accounting_periods')
            ->where('end_date', '>=', now()->toDateString())
            ->where('status', 'locked')
            ->update(['status' => 'open']);
    }

    public function down(): void
    {
    }
};
