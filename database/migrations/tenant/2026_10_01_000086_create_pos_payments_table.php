<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('pos_payment_methods');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->string('processor_name')->nullable();
            $table->timestamps();

            $table->index(['pos_sale_id', 'payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
    }
};
