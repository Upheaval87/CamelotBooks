<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->decimal('foreign_amount', 18, 2)->nullable()->after('credit');
            $table->char('foreign_currency', 3)->nullable()->after('foreign_amount');
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('foreign_currency');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('currency');
            $table->decimal('base_amount', 15, 2)->nullable()->after('exchange_rate');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('currency');
            $table->decimal('base_amount', 15, 2)->nullable()->after('exchange_rate');
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->decimal('foreign_amount', 18, 2)->nullable()->after('amount');
            $table->char('foreign_currency', 3)->nullable()->after('foreign_amount');
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('foreign_currency');
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->decimal('foreign_amount', 18, 2)->nullable()->after('amount');
            $table->char('foreign_currency', 3)->nullable()->after('foreign_amount');
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('foreign_currency');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->decimal('foreign_amount', 18, 2)->nullable()->after('amount');
            $table->char('foreign_currency', 3)->nullable()->after('foreign_amount');
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('foreign_currency');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropColumn(['foreign_amount', 'foreign_currency', 'exchange_rate']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'base_amount']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'base_amount']);
        });
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn(['foreign_amount', 'foreign_currency', 'exchange_rate']);
        });
        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropColumn(['foreign_amount', 'foreign_currency', 'exchange_rate']);
        });
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn(['foreign_amount', 'foreign_currency', 'exchange_rate']);
        });
    }
};
