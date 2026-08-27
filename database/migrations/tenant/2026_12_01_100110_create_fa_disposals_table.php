<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->date('disposal_date');
            $table->string('disposal_method', 30)->comment('sale, scrap, donation, destroyed');
            $table->decimal('proceeds_amount', 15, 2)->default(0);
            $table->decimal('disposal_cost', 15, 2)->default(0);
            $table->decimal('net_proceeds', 15, 2)->default(0);
            $table->decimal('cost_acquisition', 15, 2)->default(0);
            $table->decimal('accum_depreciation', 15, 2)->default(0);
            $table->decimal('accum_impairment', 15, 2)->default(0);
            $table->decimal('net_book_value', 15, 2)->default(0);
            $table->decimal('gain_loss', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_disposals');
    }
};
