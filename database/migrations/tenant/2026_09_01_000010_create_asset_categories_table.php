<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->string('depreciation_method_financial', 30)->default('straight_line');
            $table->integer('useful_life_financial')->comment('Months');
            $table->string('residual_value_type_financial', 10)->default('amount');
            $table->decimal('residual_value_financial', 15, 2)->default(0);

            $table->string('depreciation_method_tax', 30)->default('straight_line');
            $table->integer('useful_life_tax')->comment('Months');
            $table->string('residual_value_type_tax', 10)->default('amount');
            $table->decimal('residual_value_tax', 15, 2)->default(0);
            $table->decimal('depreciation_rate_tax', 7, 4)->nullable()->comment('Explicit rate for tax, e.g. 0.2500');

            $table->boolean('is_revaluation_enabled')->default(false);

            $table->foreignId('asset_account_id')->constrained('accounts');
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts');
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts');
            $table->foreignId('accumulated_impairment_account_id')->nullable()->constrained('accounts');
            $table->foreignId('impairment_loss_account_id')->nullable()->constrained('accounts');
            $table->foreignId('disposal_gain_loss_account_id')->nullable()->constrained('accounts');
            $table->foreignId('revaluation_surplus_account_id')->nullable()->constrained('accounts');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
