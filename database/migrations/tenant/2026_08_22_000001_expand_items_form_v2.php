<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand', 100)->nullable()->after('name');
            $table->unsignedInteger('max_stock')->nullable()->after('reorder_point');
            $table->unsignedInteger('reorder_qty')->nullable()->after('reorder_point');
            $table->unsignedSmallInteger('lead_time_days')->nullable()->after('reorder_qty');
            $table->foreignId('default_supplier_id')->nullable()->after('lead_time_days')->constrained('vendors')->nullOnDelete();
            $table->string('costing_method', 20)->default('weighted_average')->after('default_supplier_id');
            $table->boolean('low_stock_alerts')->default(true)->after('costing_method');
            $table->boolean('batch_expiry_tracking')->default(false)->after('low_stock_alerts');
            $table->boolean('serial_tracking')->default(false)->after('batch_expiry_tracking');
            $table->string('price_list', 50)->nullable()->after('serial_tracking');
            $table->decimal('opening_stock', 15, 4)->nullable()->after('price_list');
            $table->date('opening_as_at')->nullable()->after('opening_stock');
            $table->foreignId('warehouse_id')->nullable()->after('opening_as_at')->constrained('branches')->nullOnDelete();
        });

        Schema::create('items_returnable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('products')->cascadeOnDelete()->unique();
            $table->string('container_type', 30)->default('bottle');
            $table->decimal('deposit_value', 15, 2)->default(0);
            $table->string('deposit_tax_handling', 20)->default('excluded');
            $table->unsignedSmallInteger('return_window_days')->default(30);
            $table->foreignId('linked_empty_item_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('linked_filled_item_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('required_return', 20)->default('one_to_one');
            $table->foreignId('container_stock_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('container_stock_tracking')->default(true);
            $table->boolean('allow_cash_refund')->default(false);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_returnable');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['default_supplier_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn([
                'brand', 'max_stock', 'reorder_qty', 'lead_time_days',
                'default_supplier_id', 'costing_method', 'low_stock_alerts',
                'batch_expiry_tracking', 'serial_tracking', 'price_list',
                'opening_stock', 'opening_as_at', 'warehouse_id',
            ]);
        });
    }
};
