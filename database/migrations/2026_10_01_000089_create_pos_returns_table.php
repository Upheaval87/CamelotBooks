<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_sale_id')->constrained('pos_sales');
            $table->foreignId('terminal_id')->nullable()->constrained('pos_terminals');
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained();
            $table->string('return_number', 50);
            $table->date('date');
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'return_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('pos_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_sale_line_id')->constrained('pos_sale_lines');
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_returned', 12, 4)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('tax_rate', 6, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->decimal('cost_of_goods', 14, 2)->nullable();
            $table->timestamps();

            $table->index(['pos_return_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_lines');
        Schema::dropIfExists('pos_returns');
    }
};
