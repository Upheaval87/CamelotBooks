<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform quotation for a branch request. Named `billing_quotations` (not
 * `quotations`) because the tenant schema already uses `quotations` for the
 * sales-quotation domain.
 *
 * All money figures are FROZEN at issue time by the pricing service and stored
 * in `pricing_breakdown` so later price changes never rewrite an issued quote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_request_id')->constrained('branch_requests')->cascadeOnDelete();
            $table->string('quotation_number')->unique();
            $table->string('status')->default('pending'); // pending | paid | expired | cancelled
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 6, 3)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->json('pricing_breakdown')->nullable();
            $table->string('bank_reference', 40)->nullable()->unique();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'bq_company_status_idx');
            $table->index(['company_id', 'created_at'], 'bq_company_created_idx');
            $table->index('branch_request_id', 'bq_request_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_quotations');
    }
};
