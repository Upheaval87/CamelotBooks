<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('budget_templates', 'source_budget_id')) {
                $table->unsignedBigInteger('source_budget_id')->nullable()->after('basis');
            }
            if (!Schema::hasColumn('budget_templates', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('budget_templates', function (Blueprint $table) {
            $table->dropColumn(['source_budget_id', 'description']);
        });
    }
};
