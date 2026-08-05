<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_assembly')->default(false)->after('tracked_as_inventory');
        });

        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assembly_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('bom_number', 50);
            $table->string('name', 255)->nullable();
            $table->decimal('estimated_cost', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'bom_number']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('bill_of_material_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('unit_of_measure', 50)->nullable();
            $table->decimal('unit_cost', 15, 4)->nullable()->comment('Estimated cost at time of BOM definition');
            $table->timestamps();

            $table->index(['bom_id']);
        });

        Schema::create('assembly_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assembly_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('bom_id')->nullable()->constrained('bill_of_materials')->nullOnDelete();
            $table->string('build_number', 50);
            $table->enum('type', ['build', 'unbuild']);
            $table->decimal('quantity', 15, 4);
            $table->decimal('total_component_cost', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0)->comment('Cost per unit of assembled product');
            $table->string('status', 20)->default('completed');
            $table->text('memo')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->timestamps();

            $table->unique(['company_id', 'build_number']);
            $table->index(['company_id', 'assembly_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assembly_builds');
        Schema::dropIfExists('bill_of_material_lines');
        Schema::dropIfExists('bill_of_materials');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_assembly');
        });
    }
};
