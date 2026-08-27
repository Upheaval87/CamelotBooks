<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('fa_assets');
            $table->string('doc_type', 30)->comment('invoice, warranty, photo, report, other');
            $table->string('name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'asset_id']);
            $table->index(['asset_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_documents');
    }
};
