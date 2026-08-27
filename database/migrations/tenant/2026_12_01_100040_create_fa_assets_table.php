<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained();
            $table->foreignId('category_id')->constrained('fa_categories');
            $table->foreignId('class_id')->nullable()->constrained('fa_classes');

            $table->string('asset_code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('tag_number', 100)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('custodian', 255)->nullable();

            $table->date('acquisition_date');
            $table->date('in_service_date')->nullable();
            $table->date('disposal_date')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('accumulated_impairment', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);

            $table->string('depreciation_method', 30)->default('straight_line');
            $table->integer('useful_life')->comment('Months');
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->decimal('depreciation_rate', 7, 4)->nullable();
            $table->boolean('is_componentised')->default(false);
            $table->boolean('is_revalued')->default(false);

            $table->foreignId('asset_account_id')->constrained('accounts');
            $table->foreignId('accum_dep_account_id')->constrained('accounts');
            $table->foreignId('dep_expense_account_id')->constrained('accounts');
            $table->foreignId('disposal_account_id')->nullable()->constrained('accounts');

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');

            $table->string('status', 20)->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'asset_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'is_active']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_assets');
    }
};
