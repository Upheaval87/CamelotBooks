<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('next_cheque_number')->nullable()->after('bank_routing_number');
            $table->decimal('petty_cash_float', 15, 2)->nullable()->after('next_cheque_number');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['next_cheque_number', 'petty_cash_float']);
        });
    }
};
