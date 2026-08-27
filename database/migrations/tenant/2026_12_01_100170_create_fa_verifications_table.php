<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->integer('total_assets')->default(0);
            $table->integer('verified_count')->default(0);
            $table->integer('variance_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_verifications');
    }
};
