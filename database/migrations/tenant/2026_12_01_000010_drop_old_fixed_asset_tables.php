<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'depreciation_schedule_entries',
            'depreciation_runs',
            'units_of_production_usage_entries',
            'asset_revaluations',
            'asset_impairments',
            'asset_transfers',
            'asset_disposals',
            'asset_depreciation_books',
            'assets',
            'asset_categories',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Old tables cannot be restored — they are replaced by the fa_* schema.
    }
};
