<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->nullOnDelete();
            $table->string('name', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('payment_terms', 20)->default('net_30');
            $table->smallInteger('payment_terms_days')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
