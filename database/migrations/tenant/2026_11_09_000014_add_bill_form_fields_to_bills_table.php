<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->string('po_number', 60)->nullable()->after('internal_number');
            $table->string('grn_reference', 60)->nullable()->after('po_number');
            $table->text('supplier_notes')->nullable()->after('memo');
            $table->text('payment_instructions')->nullable()->after('supplier_notes');
            $table->decimal('freight_charges', 15, 2)->default(0)->after('exchange_rate');
            $table->decimal('insurance_charges', 15, 2)->default(0)->after('freight_charges');
            $table->decimal('customs_charges', 15, 2)->default(0)->after('insurance_charges');
            $table->decimal('other_charges', 15, 2)->default(0)->after('customs_charges');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'po_number',
                'grn_reference',
                'supplier_notes',
                'payment_instructions',
                'freight_charges',
                'insurance_charges',
                'customs_charges',
                'other_charges',
            ]);
        });
    }
};
