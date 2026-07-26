<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->foreignId('default_income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_inventory_asset_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('default_reorder_point', 15, 2)->nullable();
            $table->string('default_base_uom', 50)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('company_id')->constrained('item_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('item_categories');
    }
};
