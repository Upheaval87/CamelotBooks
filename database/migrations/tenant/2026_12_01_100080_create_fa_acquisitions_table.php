<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('fa_assets');
            $table->string('reference', 50);
            $table->date('acquisition_date');
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_cost', 15, 2)->default(0);
            $table->string('vendor', 255)->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained();
            $table->string('invoice_number', 100)->nullable();
            $table->string('purchase_order', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_acquisitions');
    }
};
