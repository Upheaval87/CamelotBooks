<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->date('deadline_date')->nullable();
            $table->string('deadline_granularity', 10)->nullable();
            $table->string('priority', 10)->default('medium');
            $table->string('status', 10)->default('active');
            $table->timestamp('completed_at')->nullable();
            $table->string('linkable_type')->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->string('link_label', 255)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'deadline_date']);
            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_tasks');
    }
};
