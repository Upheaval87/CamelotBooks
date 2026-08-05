<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->name('fx_rates_company');
            $table->char('currency_from', 3);
            $table->char('currency_to', 3);
            $table->decimal('rate', 18, 8);
            $table->date('effective_date');
            $table->timestamps();

            $table->unique(['company_id', 'currency_from', 'currency_to', 'effective_date'], 'fx_rates_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
