<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the 3-step text-size preset (sm/md/lg) with a multi-level font
     * scale factor (0.85 / 1.00 / 1.15 / 1.30 / 1.50). Carries existing sm/lg
     * presets over to the nearest FONT_STEPS entry, then drops the old column.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('font_scale', 3, 2)->default(1.00)->after('email');
        });

        DB::table('users')->where('text_size', 'sm')->update(['font_scale' => 0.85]);
        DB::table('users')->where('text_size', 'lg')->update(['font_scale' => 1.15]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('text_size');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('text_size', 10)->nullable()->default('md')->after('is_active');
        });

        DB::table('users')->where('font_scale', 0.85)->update(['text_size' => 'sm']);
        DB::table('users')->where('font_scale', 1.15)->update(['text_size' => 'lg']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('font_scale');
        });
    }
};
