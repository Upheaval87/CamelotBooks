<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->string('transaction_uom', 50)->nullable()->after('product_id');
            $table->decimal('transaction_qty', 10, 4)->nullable()->after('transaction_uom');
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('transaction_qty');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('transaction_uom', 50)->nullable()->after('product_id');
            $table->decimal('transaction_qty', 10, 4)->nullable()->after('transaction_uom');
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('transaction_qty');
        });

        Schema::table('pos_sale_lines', function (Blueprint $table) {
            $table->string('transaction_uom', 50)->nullable()->after('product_id');
            $table->decimal('transaction_qty', 10, 4)->nullable()->after('transaction_uom');
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('transaction_qty');
        });

        Schema::table('grn_lines', function (Blueprint $table) {
            $table->string('transaction_uom', 50)->nullable()->after('product_id');
            $table->decimal('transaction_qty', 10, 4)->nullable()->after('transaction_uom');
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('transaction_qty');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->string('transaction_uom', 50)->nullable()->after('product_id');
            $table->decimal('transaction_qty', 10, 4)->nullable()->after('transaction_uom');
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('transaction_qty');
        });
    }

    public function down(): void
    {
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->dropColumn(['transaction_uom', 'transaction_qty', 'conversion_factor']);
        });
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['transaction_uom', 'transaction_qty', 'conversion_factor']);
        });
        Schema::table('pos_sale_lines', function (Blueprint $table) {
            $table->dropColumn(['transaction_uom', 'transaction_qty', 'conversion_factor']);
        });
        Schema::table('grn_lines', function (Blueprint $table) {
            $table->dropColumn(['transaction_uom', 'transaction_qty', 'conversion_factor']);
        });
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn(['transaction_uom', 'transaction_qty', 'conversion_factor']);
        });
    }
};
