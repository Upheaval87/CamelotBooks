<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('vendor_id');
            $table->unsignedBigInteger('grn_id')->nullable()->after('purchase_order_id');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->restrictOnDelete();
            $table->foreign('grn_id')->references('id')->on('goods_received_notes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['grn_id']);
            $table->dropColumn(['purchase_order_id', 'grn_id']);
        });
    }
};
