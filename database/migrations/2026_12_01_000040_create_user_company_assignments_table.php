<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_company_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('viewer');
            $table->json('branch_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
        });

        DB::table('user_company_assignments')->insertUsing(
            ['user_id', 'company_id', 'role', 'created_at', 'updated_at'],
            DB::table('company_user')->select('user_id', 'company_id', 'role', 'created_at', 'updated_at')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_company_assignments');
    }
};
