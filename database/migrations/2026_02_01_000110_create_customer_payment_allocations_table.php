<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('created_at')->nullable();

            $table->index('customer_payment_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_allocations');
    }
};
