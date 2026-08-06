<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline payments recorded against a billing quotation.
 *
 * Only manual modes are supported today (bank_transfer | cheque | cash);
 * `payment_mode` is a plain string so an `online` mode can be added later
 * without a schema change. Payments are NEVER auto-confirmed: a staff member
 * with the billing/accounting authority must confirm before the branch limit
 * is raised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_quotation_id')->constrained('billing_quotations')->cascadeOnDelete();
            $table->string('payment_mode'); // bank_transfer | cheque | cash
            $table->string('reference_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending | confirmed | rejected
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->unsignedBigInteger('confirmed_by_user_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['billing_quotation_id', 'status'], 'pay_quote_status_idx');
            $table->index(['company_id', 'created_at'], 'pay_company_created_idx');
            $table->index('recorded_by_user_id', 'pay_recorded_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
