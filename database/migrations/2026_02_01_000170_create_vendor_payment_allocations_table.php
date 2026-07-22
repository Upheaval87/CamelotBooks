<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index('vendor_payment_id');
            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_allocations');
    }
};
