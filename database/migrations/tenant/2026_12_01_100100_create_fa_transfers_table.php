<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->date('transfer_date');
            $table->foreignId('from_branch_id')->nullable()->constrained('branches');
            $table->foreignId('to_branch_id')->nullable()->constrained('branches');
            $table->foreignId('from_cost_center_id')->nullable()->constrained('cost_centers');
            $table->foreignId('to_cost_center_id')->nullable()->constrained('cost_centers');
            $table->string('from_custodian', 255)->nullable();
            $table->string('to_custodian', 255)->nullable();
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->text('reason')->nullable();
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
        Schema::dropIfExists('fa_transfers');
    }
};
