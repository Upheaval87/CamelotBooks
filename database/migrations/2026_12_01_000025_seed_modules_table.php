<?php

use App\Services\ModuleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (ModuleRegistry::catalog() as $code => $module) {
            DB::table('modules')->insert([
                'code' => $code,
                'name' => $module['name'],
                'description' => $module['description'] ?? null,
                'is_core' => $module['is_core'],
                'sort_order' => $module['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('modules')->truncate();
    }
};
