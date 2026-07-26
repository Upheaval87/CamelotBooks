<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fact_sales', function (Blueprint $table) {
            $table->string('source_type', 20)->default('invoice')->after('item_key');
            $table->unsignedBigInteger('source_id')->after('source_type');
            $table->string('source_number', 50)->after('source_id');
            $table->string('source_status', 20)->after('source_number');
        });

        // Migrate existing invoice_id → source_id, invoice_number → source_number, invoice_status → source_status
        DB::table('fact_sales')
            ->where('source_type', 'invoice')
            ->update([
                'source_id'     => DB::raw('invoice_id'),
                'source_number' => DB::raw('invoice_number'),
                'source_status' => DB::raw('invoice_status'),
            ]);

        Schema::table('fact_sales', function (Blueprint $table) {
            $table->dropColumn(['invoice_id', 'invoice_number', 'invoice_status']);
        });

        Schema::table('fact_sales', function (Blueprint $table) {
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('fact_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('item_key');
            $table->string('invoice_number', 50)->nullable()->after('invoice_id');
            $table->string('invoice_status', 20)->nullable()->after('invoice_number');
        });

        DB::table('fact_sales')
            ->where('source_type', 'invoice')
            ->update([
                'invoice_id'     => DB::raw('source_id'),
                'invoice_number' => DB::raw('source_number'),
                'invoice_status' => DB::raw('source_status'),
            ]);

        Schema::table('fact_sales', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'source_number', 'source_status']);
        });
    }
};
