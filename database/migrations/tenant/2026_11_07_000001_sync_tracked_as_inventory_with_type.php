<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('type', 'inventory')
            ->where('tracked_as_inventory', false)
            ->update(['tracked_as_inventory' => true]);
    }

    public function down(): void
    {
        DB::table('products')
            ->where('type', 'inventory')
            ->where('tracked_as_inventory', true)
            ->update(['tracked_as_inventory' => false]);
    }
};
