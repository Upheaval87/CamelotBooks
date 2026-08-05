<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('tracked_as_inventory')->default(false)->after('type');
            $table->decimal('reorder_point', 15, 2)->nullable()->after('purchase_price');
            $table->string('unit_of_measure', 50)->nullable()->after('reorder_point');
            $table->foreignId('inventory_asset_account_id')->nullable()->after('expense_account_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['inventory_asset_account_id']);
            $table->dropColumn(['tracked_as_inventory', 'reorder_point', 'unit_of_measure', 'inventory_asset_account_id']);
        });
    }
};
