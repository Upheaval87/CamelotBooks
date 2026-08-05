<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_journal_template_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rjt_id');
            $table->foreignId('account_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->foreign('rjt_id', 'rjt_lines_rjt_fk')->references('id')->on('recurring_journal_templates')->cascadeOnDelete();
            $table->index('rjt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journal_template_lines');
    }
};
