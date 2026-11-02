<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_documents')) return;
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
