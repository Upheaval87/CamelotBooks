<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained();
            $table->foreignId('category_id')->constrained('asset_categories');

            $table->string('asset_code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('serial_number', 255)->nullable();

            $table->date('acquisition_date');
            $table->date('in_service_date');
            $table->decimal('acquisition_cost', 15, 2);

            $table->decimal('residual_value', 15, 2)->default(0);
            $table->integer('useful_life')->comment('Months');
            $table->string('depreciation_method_financial', 30);

            $table->string('depreciation_method_tax', 30);
            $table->integer('useful_life_tax')->comment('Months');
            $table->decimal('residual_value_tax', 15, 2)->default(0);
            $table->decimal('depreciation_rate_tax', 7, 4)->nullable();

            $table->boolean('is_revaluation_enabled')->default(false);
            $table->string('status', 20)->default('draft');
            $table->boolean('is_active')->default(true);

            $table->foreignId('asset_account_id')->constrained('accounts');
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts');
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts');
            $table->foreignId('accumulated_impairment_account_id')->nullable()->constrained('accounts');
            $table->foreignId('impairment_loss_account_id')->nullable()->constrained('accounts');
            $table->foreignId('disposal_gain_loss_account_id')->nullable()->constrained('accounts');
            $table->foreignId('revaluation_surplus_account_id')->nullable()->constrained('accounts');

            $table->string('acquisition_source_type')->nullable();
            $table->unsignedBigInteger('acquisition_source_id')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['company_id', 'asset_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category_id']);
            $table->index(['acquisition_source_type', 'acquisition_source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
