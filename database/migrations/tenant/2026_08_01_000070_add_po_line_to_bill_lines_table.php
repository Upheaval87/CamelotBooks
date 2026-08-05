<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')->nullable()->after('expense_account_id')->constrained('purchase_order_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_line_id']);
            $table->dropColumn('purchase_order_line_id');
        });
    }
};
