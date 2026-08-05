<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('should_never_survive', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        throw new RuntimeException('This migration must fail (intentional test failure)');
    }

    public function down(): void
    {
        Schema::dropIfExists('should_never_survive');
    }
};
