<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->string('type', 30)->comment('scheduled, unscheduled, repair');
            $table->date('maintenance_date');
            $table->date('next_due_date')->nullable();
            $table->string('provider', 255)->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('work_performed')->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['company_id', 'maintenance_date']);
            $table->index(['asset_id', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_maintenance');
    }
};
